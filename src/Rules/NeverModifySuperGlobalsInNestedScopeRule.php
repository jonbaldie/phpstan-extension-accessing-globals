<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Assign>
 */
class NeverModifySuperGlobalsInNestedScopeRule extends
    AllowSuperGlobalsInRootScopeRule
{
    public function getNodeType(): string
    {
        return Node\Expr\Assign::class;
    }

    /**
     * @param Node\Expr\Assign $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInRootScope($scope)) {
            return [];
        }

        $var = $node->var;

        while ($var instanceof Node\Expr\ArrayDimFetch) {
            $var = $var->var;
        }

        if (!$var instanceof Variable) {
            return [];
        }

        if (!is_string($var->name)) {
            return [];
        }

        if (in_array($var->name, $this->superglobals, true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is modifying superglobal variable $%s in a nested scope. Use a wrapper service instead.',
                        $var->name,
                    ),
                )
                    ->identifier("modify.superglobal.nested")
                    ->build(),
            ];
        }

        return [];
    }
}
