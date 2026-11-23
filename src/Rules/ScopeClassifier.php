<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PHPStan\Analyser\Scope;

final class ScopeClassifier
{
    public static function isRootScope(Scope $scope): bool
    {
        return self::isOutsideFunctionScope($scope) && !$scope->isInClass();
    }

    // Some opinionated rules historically also allow class declaration expressions.
    public static function isOutsideFunctionScope(Scope $scope): bool
    {
        return $scope->getFunction() === null && !self::isAnonymousScope($scope);
    }

    public static function isAnonymousScope(Scope $scope): bool
    {
        return $scope->isInAnonymousFunction();
    }
}
