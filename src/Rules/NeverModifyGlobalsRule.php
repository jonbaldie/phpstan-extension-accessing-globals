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
 * @implements Rule<Node\Expr>
 */
class NeverModifyGlobalsRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach (MutationTargetResolver::resolve($node) as $target) {
            if (!$target instanceof ArrayDimFetch) {
                continue;
            }

            $globalTarget = $target;
            while ($globalTarget->var instanceof ArrayDimFetch) {
                $globalTarget = $globalTarget->var;
            }

            if (
                !$globalTarget->var instanceof Variable ||
                $globalTarget->var->name !== "GLOBALS"
            ) {
                continue;
            }

            $key = "unknown";
            if ($globalTarget->dim instanceof String_) {
                $key = $globalTarget->dim->value;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Code is modifying global variable through $GLOBALS[\'%s\']. Use dependency injection instead.',
                    $key,
                ),
            )
                ->identifier("modify.global")
                ->build();
        }

        return $errors;
    }
}
