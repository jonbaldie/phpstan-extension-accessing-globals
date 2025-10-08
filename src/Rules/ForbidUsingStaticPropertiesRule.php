<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Identifier;

/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
class ForbidUsingStaticPropertiesRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticPropertyFetch::class;
    }

    /**
     * @param Node\Expr\StaticPropertyFetch $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method.
        // Access in the global scope (e.g. for configuration) is not our concern.
        if ($scope->getFunction() === null && !$scope->isInClass()) {
            return [];
        }

        $className = $node->class->toString();
        $propertyName = $node->name instanceof Identifier ? $node->name->toString() : '{expression}';

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing static property %s::$%s. Static properties are global state; pass the value as an argument instead.',
                    $className,
                    $propertyName
                )
            )
                ->identifier('property.static')
                ->build(),
        ];
    }
}
