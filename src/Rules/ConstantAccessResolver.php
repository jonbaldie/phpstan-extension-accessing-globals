<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\ReflectionProvider;

final class ConstantAccessResolver
{
    private readonly CalleeResolver $calleeResolver;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->calleeResolver = new CalleeResolver($reflectionProvider);
    }

    /**
     * Returns null unless the callee resolves to builtin constant(); a
     * namespaced user function named constant() does not count. Direct calls
     * need an argument, while a first-class callable is treated as dynamic
     * because its eventual arguments are unknown here.
     */
    public function resolveConstantFunctionCall(FuncCall|FunctionCallableNode $node, Scope $scope): ?ConstantAccess
    {
        $function = $this->calleeResolver->resolveFunction($node, $scope);
        if ($function === null || strtolower($function->getName()) !== 'constant') {
            return null;
        }

        if ($node instanceof FunctionCallableNode) {
            // The first-class callable's eventual argument is unknown here.
            return new DynamicConstantAccess();
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
