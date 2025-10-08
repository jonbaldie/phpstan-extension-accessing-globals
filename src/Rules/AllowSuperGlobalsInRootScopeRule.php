<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node>
 */
abstract class AllowSuperGlobalsInRootScopeRule implements Rule
{
    /**
     * @var string[]
     */
    protected array $superglobals = [
        "_GET",
        "_POST",
        "_REQUEST",
        "_SESSION",
        "_COOKIE",
        "_FILES",
        "_ENV",
        "_SERVER",
        "GLOBALS",
    ];

    protected function isInRootScope(Scope $scope): bool
    {
        return $scope->getFunction() === null && !$scope->isInClass();
    }
}
