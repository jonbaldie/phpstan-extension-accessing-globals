<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassConstFetch>
 */
class ForbidUsingClassConstantsRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassConstFetch::class;
    }

    /**
     * @param ClassConstFetch $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method.
        if ($scope->getFunction() === null) {
            return [];
        }

        // The `::class` syntax is not a constant value access, it's a language feature
        // for getting a class's fully qualified name. This is not a hidden dependency.
        if ($node->name instanceof Identifier && $node->name->toLowerString() === 'class') {
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
            RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing constant %s::%s. This creates a hidden dependency; pass the value as an argument instead.',
                    $resolvedFetchedClassName,
                    $constantName
                )
            )
                ->identifier('constant.class')
                ->build(),
        ];
    }
}
