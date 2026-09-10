<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Node\Expr\NativeTypeExpr;

final class MutationTargetResolver
{
    /**
     * @return list<Node\Expr>
     */
    public static function resolve(Node $node): array
    {
        // PHPStan emits a synthetic assignment for destructured foreach values.
        // The owning Foreach_ node is the real mutation event; processing both
        // would report every destructured target twice.
        if (
            $node instanceof Node\Expr\Assign
            && get_class($node->expr) === NativeTypeExpr::class
        ) {
            return [];
        }

        if ($node instanceof Node\Stmt\Foreach_) {
            $targets = [$node->valueVar];
        } elseif (
            !$node instanceof Node\Expr\Assign &&
            !$node instanceof Node\Expr\AssignOp &&
            !$node instanceof Node\Expr\AssignRef &&
            !$node instanceof Node\Expr\PostInc &&
            !$node instanceof Node\Expr\PreInc &&
            !$node instanceof Node\Expr\PostDec &&
            !$node instanceof Node\Expr\PreDec
        ) {
            return [];
        } else {
            $targets = [$node->var];
            if ($node instanceof Node\Expr\AssignRef) {
                $targets[] = $node->expr;
            }
        }

        $resolvedTargets = [];
        foreach ($targets as $target) {
            self::resolveTarget($target, $resolvedTargets);
        }

        return $resolvedTargets;
    }

    /**
     * @param list<Node\Expr> $resolvedTargets
     */
    private static function resolveTarget(Node\Expr $target, array &$resolvedTargets): void
    {
        if (!$target instanceof Node\Expr\Array_ && !$target instanceof Node\Expr\List_) {
            $resolvedTargets[] = $target;
            return;
        }

        foreach ($target->items as $item) {
            if ($item === null) {
                continue;
            }

            self::resolveTarget($item->value, $resolvedTargets);
        }
    }
}
