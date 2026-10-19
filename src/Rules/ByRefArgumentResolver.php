<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Resolves the arguments of an invocation that are passed to a by-reference
 * parameter, and are therefore mutated by the call (e.g.
 * `array_pop($_SESSION['items'])`). Parameter reflection is used instead of a
 * hardcoded function list so ordinary by-value calls are never flagged.
 */
final class ByRefArgumentResolver
{
    private readonly CalleeResolver $calleeResolver;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->calleeResolver = new CalleeResolver($reflectionProvider);
    }

    /**
     * @return list<Node\Expr>
     */
    public function resolve(Node\Expr $call, Scope $scope): array
    {
        if (!$call instanceof Node\Expr\CallLike) {
            return [];
        }

        if ($call instanceof Node\Expr\MethodCall && $call->hasAttribute('virtualNullsafeMethodCall')) {
            // PHPStan's walk also visits `$obj?->method()` as a virtual
            // MethodCall sharing the same arguments; the NullsafeMethodCall
            // itself reports them, so the virtual node must not report again.
            return [];
        }

        if ($call instanceof Node\Expr\FuncCall) {
            $function = $this->calleeResolver->resolveFunction($call, $scope);
            if ($function?->isBuiltin() && strtolower($function->getName()) === 'array_multisort') {
                return $this->resolveAllArguments($call->getArgs());
            }
        }

        return $this->resolveByReferenceArguments(
            $this->calleeResolver->getParameterAcceptors($call, $scope),
            $call->getArgs(),
        );
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
