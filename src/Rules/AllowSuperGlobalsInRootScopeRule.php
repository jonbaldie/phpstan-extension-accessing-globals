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
        // Closures and arrow functions defined at file scope have no enclosing
        // function, but their bodies are still nested scopes, not root code.
        return $scope->getFunction() === null
            && !$scope->isInClass()
            && !$scope->isInAnonymousFunction();
    }
}
