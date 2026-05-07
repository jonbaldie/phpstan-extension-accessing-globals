<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class NeverModifySuperGlobalsInNestedScopeRule extends
    AllowSuperGlobalsInRootScopeRule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInRootScope($scope)) {
            return [];
        }

        $var = match (true) {
            $node instanceof Node\Expr\Assign,
            $node instanceof AssignOp,
            $node instanceof PreInc,
            $node instanceof PreDec,
            $node instanceof PostInc,
            $node instanceof PostDec => $node->var,
            default => null,
        };

        if ($var === null) {
            return [];
        }

        while ($var instanceof ArrayDimFetch) {
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
                        'Code is modifying superglobal variable $%s in a nested scope. Return the new value instead.',
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
