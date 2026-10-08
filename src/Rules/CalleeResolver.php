<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Resolves the function invoked by a call or represented by a first-class
 * function callable, and exposes its parameter acceptors to call analysis.
 */
final class CalleeResolver
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * Returns the function a direct or constant-string function call invokes.
     * Unknown, ambiguous, and non-function callable expressions return null.
     */
    public function resolveFunction(FuncCall|FunctionCallableNode $call, Scope $scope): ?FunctionReflection
    {
        $callee = $call instanceof FuncCall ? $call->name : $call->getName();

        if ($callee instanceof Name) {
            return $this->resolveNamedFunction($callee, $scope);
        }

        $constantStrings = $scope->getType($callee)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        $functionName = ltrim($constantStrings[0]->getValue(), '\\');
        if ($functionName === '') {
            return null;
        }

        // Callable strings are runtime names, not names subject to the current
        // namespace's function fallback rules.
        return $this->resolveNamedFunction(new FullyQualified($functionName), $scope);
    }

    /**
     * @return list<ParametersAcceptor>
     */
    public function getParameterAcceptors(
        FuncCall $call,
        Scope $scope,
        ?FunctionReflection $resolvedFunction = null,
    ): array {
        if ($resolvedFunction !== null) {
            return $resolvedFunction->getVariants();
        }

        if (!$call->name instanceof Expr) {
            return [];
        }

        $calleeType = $scope->getType($call->name);
        if (!$calleeType->isCallable()->yes()) {
            return [];
        }

        return $calleeType->getCallableParametersAcceptors($scope);
    }

    private function resolveNamedFunction(Name $name, Scope $scope): ?FunctionReflection
    {
        if (!$this->reflectionProvider->hasFunction($name, $scope)) {
            return null;
        }

        return $this->reflectionProvider->getFunction($name, $scope);
    }
}
