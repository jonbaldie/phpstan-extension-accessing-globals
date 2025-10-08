<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Variable>
 */
class NeverAccessSuperGlobalsInNestedScopeRule extends
    AllowSuperGlobalsInRootScopeRule
{
    public function getNodeType(): string
    {
        return Node\Expr\Variable::class;
    }

    /**
     * @param Node\Expr\Variable $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInRootScope($scope)) {
            return [];
        }

        if (!is_string($node->name)) {
            return [];
        }

        if (in_array($node->name, $this->superglobals, true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is accessing superglobal variable $%s in a nested scope. Pass the value as an argument instead.',
                        $node->name,
                    ),
                )
                    ->identifier("access.superglobal.nested")
                    ->build(),
            ];
        }

        return [];
    }
}
