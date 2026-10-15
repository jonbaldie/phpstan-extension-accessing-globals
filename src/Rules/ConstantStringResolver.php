<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;

/**
 * Resolves an expression to the one constant string PHPStan knows it
 * evaluates to. Unions of several strings, and unions that mix a string with
 * any non-string alternative, are not known names and resolve to null.
 */
final class ConstantStringResolver
{
    public static function resolve(Expr $expr, Scope $scope): ?string
    {
        // PHPStan returns no constant strings for a union with any non-string
        // member, so a possible int or null alternative is never discarded.
        $constantStrings = $scope->getType($expr)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}
