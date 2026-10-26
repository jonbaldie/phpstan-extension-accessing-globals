<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr>
 */
class ForbidUsingClassConstantsRule implements Rule
{
    private readonly ConstantAccessResolver $constantAccessResolver;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->constantAccessResolver = new ConstantAccessResolver($reflectionProvider);
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
        // Root-scope closures and arrow functions are nested scopes too.
        if ($scope->getFunction() === null && !$scope->isInAnonymousFunction()) {
            return [];
        }

        $access = $this->constantAccessResolver->resolve($node, $scope);
        // Global and dynamic accesses belong to the global-constant rule only.
        if (!$access instanceof ClassConstantAccess) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing constant %s::%s. This creates a hidden dependency; pass the value as an argument instead.',
                    $access->className,
                    $access->constantName
                )
            )
                ->identifier('constant.class')
                ->build(),
        ];
    }
}
