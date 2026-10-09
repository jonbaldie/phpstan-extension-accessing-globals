<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
class ForbidUsingStaticPropertiesRule implements Rule
{
    private readonly StaticPropertyAccessResolver $staticPropertyAccessResolver;

    public function __construct()
    {
        $this->staticPropertyAccessResolver = new StaticPropertyAccessResolver();
    }

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
        // Closures and arrow functions defined at file scope have no enclosing
        // function, but their bodies are nested scopes, not root code.
        if ($scope->getFunction() === null
            && !$scope->isInClass()
            && !$scope->isInAnonymousFunction()
        ) {
            return [];
        }

        $access = $this->staticPropertyAccessResolver->resolve($node, $scope);
        if ($access === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing static property %s::$%s. Static properties are global state; pass the value as an argument instead.',
                    $access->className,
                    $access->propertyName
                )
            )
                ->identifier('property.static')
                ->build(),
        ];
    }
}
