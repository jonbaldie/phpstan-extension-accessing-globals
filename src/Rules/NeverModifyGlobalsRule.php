<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;

/**
 * @implements Rule<Node\Expr\Assign>
 */
class NeverModifyGlobalsRule implements Rule
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
        $errors = [];
        $assignedTo = $node->var;

        if (!$assignedTo instanceof ArrayDimFetch) {
            return [];
        }

        if (
            !$assignedTo->var instanceof Variable ||
            $assignedTo->var->name !== "GLOBALS"
        ) {
            return [];
        }

        $key = "unknown";
        if ($assignedTo->dim instanceof String_) {
            $key = $assignedTo->dim->value;
        }

        $errors[] = RuleErrorBuilder::message(
            sprintf(
                'Code is modifying global variable through $GLOBALS[\'%s\']. Use dependency injection instead.',
                $key,
            ),
        )
            ->identifier("modify.global")
            ->build();

        return $errors;
    }
}
