<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * Names a global variable however it is spelled: `$name`, `${$expr}`, or
 * `$GLOBALS[<key>]`. Non-literal names resolve only when PHPStan knows the
 * expression is exactly one constant string.
 */
final class GlobalVariableNameResolver
{
    public static function resolve(Node\Expr\Variable $variable, Scope $scope): ?string
    {
        if (is_string($variable->name)) {
            return $variable->name;
        }

        return self::constantString($variable->name, $scope);
    }

    /**
     * `$GLOBALS[<dim>]`: the named global, or null when the fetch is not on
     * `$GLOBALS` or the key is not one known string.
     */
    public static function resolveGlobalsKey(Node\Expr\ArrayDimFetch $fetch, Scope $scope): ?string
    {
        if (
            !$fetch->var instanceof Node\Expr\Variable
            || self::resolve($fetch->var, $scope) !== 'GLOBALS'
            || $fetch->dim === null
        ) {
            return null;
        }

        return self::constantString($fetch->dim, $scope);
    }

    /**
     * The single constant string an expression evaluates to, or null.
     */
    public static function constantString(Node\Expr $expr, Scope $scope): ?string
    {
        $constantStrings = $scope->getType($expr)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}
