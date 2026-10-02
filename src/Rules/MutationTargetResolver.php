<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\NativeTypeExpr;

final class MutationTargetResolver
{
    public function __construct(
        private readonly ByRefArgumentResolver $byRefArgumentResolver,
    ) {
    }

    /**
     * @return list<Node\Expr>
     */
    public function resolve(Node $node, Scope $scope): array
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

        if ($node instanceof Node\Stmt\Unset_) {
            $targets = $node->vars;
        } elseif ($node instanceof Node\Stmt\Foreach_) {
            $targets = [$node->valueVar];
            if ($node->keyVar !== null) {
                // `foreach ($data as $k => $v)` writes the current key into
                // $k on every iteration, just like the value target.
                $targets[] = $node->keyVar;
            }
            if ($node->byRef) {
                // With `foreach ($container as &$value)`, writes to $value
                // alias into the container, so the container itself is a
                // mutation target.
                $targets[] = $node->expr;
            }
        } elseif ($node instanceof Node\Stmt\Catch_) {
            // `catch (\Exception $e)` assigns the caught exception to $e.
            // Since PHP 8.0 the variable is optional.
            if ($node->var === null) {
                return [];
            }
            $targets = [$node->var];
        } elseif ($node instanceof Node\Expr\CallLike) {
            return $this->byRefArgumentResolver->resolve($node, $scope);
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
            } elseif (
                $node instanceof Node\Expr\Assign
                && !$node->var instanceof Node\Expr\Array_
                && !$node->var instanceof Node\Expr\List_
            ) {
                // A plain assignment stores the assigned expression in the
                // target container. By-reference items in an array or list
                // literal (`$arr = [&$db];`) bind a live alias of the
                // referenced variable, which later writes through the alias
                // mutate - resolve those items as mutation targets of the
                // variable they reference, like the direct AssignRef form.
                // Destructuring assignments (list/array LHS) dereference the
                // items instead, so no reference survives there.
                self::collectByRefReferences($node->expr, $targets);
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

    /**
     * Collect the expressions referenced by by-reference items of an array or
     * list literal, recursively through nested literals.
     *
     * @param Node\Expr $expr
     * @param list<Node\Expr> $targets
     */
    private static function collectByRefReferences(Node\Expr $expr, array &$targets): void
    {
        if (!$expr instanceof Node\Expr\Array_ && !$expr instanceof Node\Expr\List_) {
            return;
        }

        foreach ($expr->items as $item) {
            if ($item === null) {
                continue;
            }

            if ($item->byRef) {
                $targets[] = $item->value;
            }

            self::collectByRefReferences($item->value, $targets);
        }
    }
}
