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

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
        $this->calleeResolver = new CalleeResolver($reflectionProvider);
    }

    /**
     * Classifies constant access syntax; reporting scope and diagnostics belong
     * to the rules. Class exclusions are shared by direct and constant() access.
     */
    public function resolve(Node\Expr $expr, Scope $scope): ?ConstantAccess
    {
        if ($expr instanceof Node\Expr\ConstFetch) {
            $name = $expr->name->toString();
            if (in_array(strtolower($name), ['true', 'false', 'null'], true)) {
                return null;
            }

            return new GlobalConstantAccess($name);
        }

        if ($expr instanceof Node\Expr\ClassConstFetch) {
            if (!$expr->name instanceof Node\Identifier) {
                return null;
            }

            $className = ClassOperandResolver::resolveName($expr->class, $scope);
            if ($className === null) {
                return null;
            }

            if ($expr->class instanceof Node\Name) {
                // Preserve pseudo-class names for the common exclusion below.
                if (!in_array(strtolower($className), ['self', 'parent', 'static'], true)) {
                    $className = $scope->resolveName($expr->class);
                }
            }

            return $this->resolveClassConstant($className, $expr->name->toString(), $scope);
        }

        if ($expr instanceof FuncCall || $expr instanceof FunctionCallableNode) {
            return $this->resolveConstantFunctionCall($expr, $scope);
        }

        return null;
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

        $constantName = ConstantStringResolver::resolve($args[0]->value, $scope);

        if ($constantName === null) {
            // The name is not one statically known string; the lookup may
            // resolve to either a global or a class constant at runtime.
            return new DynamicConstantAccess();
        }

        // constant('Foo::BAR') reads a class constant (PHP 8.3+).
        if (str_contains($constantName, '::')) {
            [$className, $classConstantName] = explode('::', $constantName, 2);

            return $this->resolveClassConstant($className, $classConstantName, $scope);
        }

        return new GlobalConstantAccess($constantName);
    }

    private function resolveClassConstant(string $className, string $constantName, Scope $scope): ?ClassConstantAccess
    {
        if ($className === '' || $constantName === '' || strtolower($constantName) === 'class') {
            return null;
        }

        if (in_array(strtolower($className), ['self', 'parent', 'static'], true)) {
            return null;
        }

        $className = ltrim($className, '\\');
        $currentClass = $scope->getClassReflection();
        if ($currentClass !== null && strcasecmp($currentClass->getName(), $className) === 0) {
            return null;
        }

        // Enum cases are data type variants rather than configuration values.
        if ($this->reflectionProvider->hasClass($className)
            && $this->reflectionProvider->getClass($className)->hasEnumCase($constantName)) {
            return null;
        }

        return new ClassConstantAccess($className, $constantName);
    }
}
