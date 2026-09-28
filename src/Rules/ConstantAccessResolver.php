<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final class ConstantAccessResolver
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * Returns null unless the call is the builtin constant() with at least one
     * argument; a namespaced user function named constant() does not count.
     */
    public function resolveConstantFunctionCall(FuncCall $node, Scope $scope): ?ConstantAccess
    {
        if (!$node->name instanceof Name) {
            // Dynamic function calls like `$functionName()`.
            return null;
        }

        $resolvedFunctionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);

        if ($resolvedFunctionName === null || strtolower($resolvedFunctionName) !== 'constant') {
            return null;
        }

        $args = $node->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $constantNameArgument = $args[0]->value;

        if (!$constantNameArgument instanceof Node\Scalar\String_) {
            // The name is not statically known; the lookup may resolve to
            // either a global or a class constant at runtime.
            return new DynamicConstantAccess();
        }

        $constantName = $constantNameArgument->value;

        // constant('Foo::BAR') reads a class constant (PHP 8.3+).
        if (str_contains($constantName, '::')) {
            [$className, $classConstantName] = explode('::', $constantName, 2);

            return new ClassConstantAccess($className, $classConstantName);
        }

        return new GlobalConstantAccess($constantName);
    }
}
