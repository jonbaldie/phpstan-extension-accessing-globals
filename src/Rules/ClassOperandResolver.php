<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

/**
 * Resolves the class operand of a static property fetch, static call, `new`,
 * or class constant fetch (`Config::`, `$config::`, `$className::`). An
 * object operand names its class; any other expression is read as a
 * class-string, as PHP does at runtime.
 */
final class ClassOperandResolver
{
    /**
     * Null when the operand names no class.
     */
    public static function resolveType(Name|Expr $class, Scope $scope): ?Type
    {
        if ($class instanceof Name) {
            return $scope->resolveTypeByName($class);
        }

        $classType = $scope->getType($class);
        if (!$classType->canCallMethods()->yes()) {
            $classType = $classType->getClassStringObjectType();
        }

        if ($classType->getObjectClassNames() === []) {
            return null;
        }

        return $classType;
    }

    /**
     * A named class (including `self`, `static`, and `parent`) is kept as
     * written. Null when the operand is not exactly one class.
     */
    public static function resolveName(Name|Expr $class, Scope $scope): ?string
    {
        if ($class instanceof Name) {
            return $class->toString();
        }

        $classNames = self::resolveType($class, $scope)?->getObjectClassNames() ?? [];
        if (count($classNames) !== 1) {
            return null;
        }

        return $classNames[0];
    }
}
