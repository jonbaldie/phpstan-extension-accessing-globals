<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\TypeCombinator;

/**
 * Resolves functions and exposes parameter acceptors for all invocation forms
 * to call analysis.
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

        $functionName = ltrim(ConstantStringResolver::resolve($callee, $scope) ?? '', '\\');
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
        CallLike $call,
        Scope $scope,
    ): array {
        if ($call instanceof Expr\MethodCall || $call instanceof Expr\NullsafeMethodCall) {
            $methodName = $this->resolveMethodName($call->name, $scope);
            if ($methodName === null) {
                return [];
            }

            $callerType = $scope->getType($call->var);
            if ($call instanceof Expr\NullsafeMethodCall) {
                // The call only happens when the receiver is not null.
                $callerType = TypeCombinator::removeNull($callerType);
            }

            return $scope->getMethodReflection($callerType, $methodName)?->getVariants() ?? [];
        }

        if ($call instanceof Expr\StaticCall) {
            $methodName = $this->resolveMethodName($call->name, $scope);
            if ($methodName === null) {
                return [];
            }

            $classType = ClassOperandResolver::resolveType($call->class, $scope);
            if ($classType === null) {
                return [];
            }

            return $scope->getMethodReflection($classType, $methodName)?->getVariants() ?? [];
        }

        if ($call instanceof Expr\New_) {
            $classType = $call->class instanceof Node\Stmt\Class_
                ? $scope->getType($call)
                : ClassOperandResolver::resolveType($call->class, $scope);
            if ($classType === null) {
                return [];
            }

            return $scope->getMethodReflection($classType, '__construct')?->getVariants() ?? [];
        }

        if (!$call instanceof FuncCall) {
            return [];
        }

        $function = $this->resolveFunction($call, $scope);
        if ($function !== null) {
            return $function->getVariants();
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

    private function resolveMethodName(Node\Identifier|Expr $name, Scope $scope): ?string
    {
        if ($name instanceof Node\Identifier) {
            return $name->toString();
        }

        return ConstantStringResolver::resolve($name, $scope);
    }

    private function resolveNamedFunction(Name $name, Scope $scope): ?FunctionReflection
    {
        if (!$this->reflectionProvider->hasFunction($name, $scope)) {
            return null;
        }

        return $this->reflectionProvider->getFunction($name, $scope);
    }
}
