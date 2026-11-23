<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PHPStan\Analyser\Scope;

final class ScopeClassifier
{
    public static function isRootScope(Scope $scope): bool
    {
        return $scope->getFunction() === null
            && !$scope->isInClass()
            && !self::isAnonymousScope($scope);
    }

    public static function isAnonymousScope(Scope $scope): bool
    {
        return $scope->isInAnonymousFunction();
    }
}
