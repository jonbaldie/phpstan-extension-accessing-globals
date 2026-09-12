<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr>
 */
class ForbidUsingClassConstantsRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    )
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method.
        // Closures and arrow functions defined at file scope have no enclosing
        // function, but their bodies are nested scopes, not root code.
        if ($scope->getFunction() === null && !$scope->isInAnonymousFunction()) {
            return [];
        }

        if ($node instanceof ClassConstFetch) {
            return $this->processClassConstFetch($node, $scope);
        }

        if ($node instanceof FuncCall) {
            return $this->processConstantFunctionCall($node, $scope);
        }

        return [];
    }

    private function processClassConstFetch(ClassConstFetch $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            // Dynamic class constant fetches like `Config::{$name}` cannot be resolved
            // to a specific constant. Skip them silently.
            return [];
        }

        // The `::class` syntax is not a constant value access, it's a language feature
        // for getting a class's fully qualified name. This is not a hidden dependency.
        if ($node->name->toLowerString() === 'class') {
            return [];
        }

        $classNode = $node->class;
        if (!$classNode instanceof Name) {
            // This handles dynamic class constant fetches like `$className::CONSTANT`.
            // While this is also a form of dependency, it's a more complex case.
            // This rule focuses on direct, static dependencies.
            return [];
        }

        $className = $classNode->toString();

        // Accessing constants on `self`, `parent`, or `static` is part of the class's
        // own implementation and is not a dependency on an *external* class.
        if (in_array(strtolower($className), ['self', 'parent', 'static'], true)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        $resolvedFetchedClassName = $scope->resolveName($classNode);

        // If we are inside a class, check if the constant fetch is on the class itself.
        if ($classReflection !== null && $classReflection->getName() === $resolvedFetchedClassName) {
            return [];
        }

        // If we've reached this point, the code is accessing a constant on a different,
        // external class. This creates a hidden, compile-time dependency.
        $constantName = $node->name->toString();

        return [
            $this->buildClassConstantError($resolvedFetchedClassName, $constantName),
        ];
    }

    private function processConstantFunctionCall(FuncCall $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            // Dynamic function calls like `$functionName()`.
            return [];
        }

        $resolvedFunctionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);

        if ($resolvedFunctionName === null || strtolower($resolvedFunctionName) !== 'constant') {
            return [];
        }

        $args = $node->getArgs();

        if (count($args) === 0) {
            return [];
        }

        $constantNameArgument = $args[0]->value;

        if (!$constantNameArgument instanceof Node\Scalar\String_) {
            return [];
        }

        $constantString = $constantNameArgument->value;

        if (!str_contains($constantString, '::')) {
            return [];
        }

        [$className, $constantName] = explode('::', $constantString, 2);

        if ($className === '' || $constantName === '') {
            return [];
        }

        if (strtolower($constantName) === 'class') {
            return [];
        }

        if (in_array(strtolower($className), ['self', 'parent', 'static'], true)) {
            return [];
        }

        $normalizedClassName = ltrim($className, '\\');

        $classReflection = $scope->getClassReflection();

        if ($classReflection !== null) {
            $currentClassName = $classReflection->getName();

            if (strtolower($normalizedClassName) === strtolower($currentClassName)) {
                return [];
            }
        }

        return [
            $this->buildClassConstantError($normalizedClassName, $constantName),
        ];
    }

    private function buildClassConstantError(string $className, string $constantName): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Code is accessing constant %s::%s. This creates a hidden dependency; pass the value as an argument instead.',
                $className,
                $constantName
            )
        )
            ->identifier('constant.class')
            ->build();
    }
}
