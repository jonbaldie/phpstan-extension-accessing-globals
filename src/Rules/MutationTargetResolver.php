<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Reflection\ReflectionProvider;

final class MutationTargetResolver
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * @return list<Node\Expr>
     */
    public function resolve(Node $node, Scope $scope): array
    {
        // PHPStan emits a synthetic assignment for destructured foreach values.
        // The owning Foreach_ node is the real mutation event; processing both
        // would report every destructured target twice.
        if (
            $node instanceof Node\Expr\Assign
            && get_class($node->expr) === NativeTypeExpr::class
        ) {
            return [];
        }

        if ($node instanceof Node\Stmt\Unset_) {
            $targets = $node->vars;
        } elseif ($node instanceof Node\Stmt\Foreach_) {
            $targets = [$node->valueVar];
            if ($node->keyVar !== null) {
                // `foreach ($data as $k => $v)` writes the current key into
                // $k on every iteration, just like the value target.
                $targets[] = $node->keyVar;
            }
            if ($node->byRef) {
                // With `foreach ($container as &$value)`, writes to $value
                // alias into the container, so the container itself is a
                // mutation target.
                $targets[] = $node->expr;
            }
        } elseif ($node instanceof Node\Expr\FuncCall) {
            return $this->resolveFuncCall($node, $scope);
        } elseif ($node instanceof Node\Expr\MethodCall) {
            return $this->resolveMethodCall($node, $scope);
        } elseif ($node instanceof Node\Expr\StaticCall) {
            return $this->resolveStaticCall($node, $scope);
        } elseif (
            !$node instanceof Node\Expr\Assign &&
            !$node instanceof Node\Expr\AssignOp &&
            !$node instanceof Node\Expr\AssignRef &&
            !$node instanceof Node\Expr\PostInc &&
            !$node instanceof Node\Expr\PreInc &&
            !$node instanceof Node\Expr\PostDec &&
            !$node instanceof Node\Expr\PreDec
        ) {
            return [];
        } else {
            $targets = [$node->var];
            if ($node instanceof Node\Expr\AssignRef) {
                $targets[] = $node->expr;
            }
        }

        $resolvedTargets = [];
        foreach ($targets as $target) {
            self::resolveTarget($target, $resolvedTargets);
        }

        return $resolvedTargets;
    }

    /**
     * A function whose parameter is declared by-reference mutates the passed
     * value (e.g. `array_pop($_SESSION['items'])`). Resolve those arguments as
     * mutation targets, using parameter reflection instead of a hardcoded
     * function list so ordinary by-value calls are never flagged.
     *
     * @return list<Node\Expr>
     */
    private function resolveFuncCall(Node\Expr\FuncCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }

        if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
            return [];
        }

        $function = $this->reflectionProvider->getFunction($node->name, $scope);

        return $this->resolveParametersAcceptorArguments($function->getVariants(), $node->getArgs());
    }

    /**
     * @return list<Node\Expr>
     */
    private function resolveMethodCall(
        Node\Expr\MethodCall $node,
        Scope $scope,
    ): array {
        $methodName = $this->resolveMethodName($node->name, $scope);
        if ($methodName === null) {
            return [];
        }

        $callerType = $scope->getType($node->var);
        $method = $scope->getMethodReflection($callerType, $methodName);
        if ($method === null) {
            return [];
        }

        return $this->resolveParametersAcceptorArguments($method->getVariants(), $node->getArgs());
    }

    /**
     * @return list<Node\Expr>
     */
    private function resolveStaticCall(Node\Expr\StaticCall $node, Scope $scope): array
    {
        $methodName = $this->resolveMethodName($node->name, $scope);
        if ($methodName === null) {
            return [];
        }

        if ($node->class instanceof Node\Name) {
            $classType = $scope->resolveTypeByName($node->class);
        } elseif ($node->class instanceof Node\Expr) {
            $classType = $scope->getType($node->class);
            if (!$classType->canCallMethods()->yes()) {
                $classType = $classType->getClassStringObjectType();
            }
        } else {
            return [];
        }

        $method = $scope->getMethodReflection($classType, $methodName);
        if ($method === null) {
            return [];
        }

        return $this->resolveParametersAcceptorArguments($method->getVariants(), $node->getArgs());
    }

    private function resolveMethodName(Node\Identifier|Node\Expr $name, Scope $scope): ?string
    {
        if ($name instanceof Node\Identifier) {
            return $name->toString();
        }

        $constantStrings = $scope->getType($name)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }

    /**
     * @param list<\PHPStan\Reflection\ParametersAcceptor> $variants
     * @param list<Node\Arg> $args
     * @return list<Node\Expr>
     */
    private function resolveParametersAcceptorArguments(array $variants, array $args): array
    {
        $targets = [];
        foreach ($variants as $variant) {
            $parameters = $variant->getParameters();
            $parameterCount = count($parameters);
            $variadicParameter = $variant->isVariadic() && $parameterCount > 0
                ? $parameters[$parameterCount - 1]
                : null;

            foreach ($args as $index => $arg) {
                // Spread arguments have an unknown mapping onto parameters.
                if ($arg->unpack) {
                    continue;
                }

                if ($arg->name !== null) {
                    $parameter = null;
                    foreach ($parameters as $candidate) {
                        if ($candidate->getName() === $arg->name->toString()) {
                            $parameter = $candidate;
                            break;
                        }
                    }
                } else {
                    if ($variadicParameter !== null && $index >= $parameterCount - 1) {
                        $parameter = $variadicParameter;
                    } elseif ($index < $parameterCount) {
                        $parameter = $parameters[$index];
                    } else {
                        $parameter = null;
                    }
                }

                if ($parameter === null || !$parameter->passedByReference()->yes()) {
                    continue;
                }

                // Variants share arguments; report each mutating argument once.
                if (!in_array($arg->value, $targets, true)) {
                    $targets[] = $arg->value;
                }
            }
        }

        return $targets;
    }

    /**
     * @param list<Node\Expr> $resolvedTargets
     */
    private static function resolveTarget(Node\Expr $target, array &$resolvedTargets): void
    {
        if (!$target instanceof Node\Expr\Array_ && !$target instanceof Node\Expr\List_) {
            $resolvedTargets[] = $target;
            return;
        }

        foreach ($target->items as $item) {
            if ($item === null) {
                continue;
            }

            self::resolveTarget($item->value, $resolvedTargets);
        }
    }
}
