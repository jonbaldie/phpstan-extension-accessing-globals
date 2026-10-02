<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Resolves the arguments of an invocation that are passed to a by-reference
 * parameter, and are therefore mutated by the call (e.g.
 * `array_pop($_SESSION['items'])`). Parameter reflection is used instead of a
 * hardcoded function list so ordinary by-value calls are never flagged.
 */
final class ByRefArgumentResolver
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * @return list<Node\Expr>
     */
    public function resolve(Node\Expr $call, Scope $scope): array
    {
        if ($call instanceof Node\Expr\FuncCall) {
            return $this->resolveFuncCall($call, $scope);
        }

        if ($call instanceof Node\Expr\MethodCall && $call->hasAttribute('virtualNullsafeMethodCall')) {
            // PHPStan's walk also visits `$obj?->method()` as a virtual
            // MethodCall sharing the same arguments; the NullsafeMethodCall
            // itself reports them, so the virtual node must not report again.
            return [];
        }

        if ($call instanceof Node\Expr\MethodCall || $call instanceof Node\Expr\NullsafeMethodCall) {
            return $this->resolveMethodCall($call, $scope);
        }

        if ($call instanceof Node\Expr\StaticCall) {
            return $this->resolveStaticCall($call, $scope);
        }

        if ($call instanceof Node\Expr\New_) {
            return $this->resolveNew($call, $scope);
        }

        return [];
    }

    /**
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

        if ($function->isBuiltin() && strtolower($function->getName()) === 'array_multisort') {
            return $this->resolveAllArguments($node->getArgs());
        }

        return $this->resolveByReferenceArguments($function->getVariants(), $node->getArgs());
    }

    /**
     * @param list<Node\Arg> $args
     * @return list<Node\Expr>
     */
    private function resolveAllArguments(array $args): array
    {
        $targets = [];
        foreach ($args as $arg) {
            if ($arg->unpack) {
                continue;
            }

            if (!in_array($arg->value, $targets, true)) {
                $targets[] = $arg->value;
            }
        }

        return $targets;
    }

    /**
     * @return list<Node\Expr>
     */
    private function resolveMethodCall(
        Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $node,
        Scope $scope,
    ): array {
        $methodName = $this->resolveMethodName($node->name, $scope);
        if ($methodName === null) {
            return [];
        }

        $callerType = $scope->getType($node->var);
        if ($node instanceof Node\Expr\NullsafeMethodCall) {
            // The call only happens when the receiver is not null.
            $callerType = TypeCombinator::removeNull($callerType);
        }

        $method = $scope->getMethodReflection($callerType, $methodName);
        if ($method === null) {
            return [];
        }

        return $this->resolveByReferenceArguments($method->getVariants(), $node->getArgs());
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

        $method = $scope->getMethodReflection($this->resolveClassType($node->class, $scope), $methodName);
        if ($method === null) {
            return [];
        }

        return $this->resolveByReferenceArguments($method->getVariants(), $node->getArgs());
    }

    /**
     * @return list<Node\Expr>
     */
    private function resolveNew(Node\Expr\New_ $node, Scope $scope): array
    {
        $classType = $node->class instanceof Node\Stmt\Class_
            ? $scope->getType($node)
            : $this->resolveClassType($node->class, $scope);

        $method = $scope->getMethodReflection($classType, '__construct');
        if ($method === null) {
            return [];
        }

        return $this->resolveByReferenceArguments($method->getVariants(), $node->getArgs());
    }

    private function resolveClassType(Node\Name|Node\Expr $class, Scope $scope): Type
    {
        if ($class instanceof Node\Name) {
            return $scope->resolveTypeByName($class);
        }

        $classType = $scope->getType($class);
        if (!$classType->canCallMethods()->yes()) {
            $classType = $classType->getClassStringObjectType();
        }

        return $classType;
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
    private function resolveByReferenceArguments(array $variants, array $args): array
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
}
