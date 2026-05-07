<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;

/**
 * @implements Rule<Node\Expr>
 */
class NeverModifyGlobalsRule implements Rule
{
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $assignedTo = $this->findModifiedTarget($node);

        if (!$assignedTo instanceof ArrayDimFetch) {
            return [];
        }

        $rootArrayFetch = $assignedTo;
        while ($rootArrayFetch->var instanceof ArrayDimFetch) {
            $rootArrayFetch = $rootArrayFetch->var;
        }

        if (
            !$rootArrayFetch->var instanceof Variable ||
            $rootArrayFetch->var->name !== "GLOBALS"
        ) {
            return [];
        }

        $key = "unknown";
        if ($rootArrayFetch->dim instanceof String_) {
            $key = $rootArrayFetch->dim->value;
        }

        return [RuleErrorBuilder::message(
            sprintf(
                'Code is modifying global variable through $GLOBALS[\'%s\']. Use dependency injection instead.',
                $key,
            ),
        )
            ->identifier("modify.global")
            ->build()];
    }

    private function findModifiedTarget(Expr $node): ?Expr
    {
        if (
            $node instanceof Expr\Assign ||
            $node instanceof Expr\AssignOp ||
            $node instanceof Expr\PreInc ||
            $node instanceof Expr\PreDec ||
            $node instanceof Expr\PostInc ||
            $node instanceof Expr\PostDec
        ) {
            return $node->var;
        }

        return null;
    }
}
