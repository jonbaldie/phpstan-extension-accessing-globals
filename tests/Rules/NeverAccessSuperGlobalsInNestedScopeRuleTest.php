<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverAccessSuperGlobalsInNestedScopeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverAccessSuperGlobalsInNestedScopeRule>
 */
class NeverAccessSuperGlobalsInNestedScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverAccessSuperGlobalsInNestedScopeRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/access-superglobals-in-nested-scope.php"],
            [
                [
                    'Code is accessing superglobal variable $_GET in a nested scope. Use a wrapper service instead.',
                    9,
                ],
                [
                    'Code is accessing superglobal variable $_POST in a nested scope. Use a wrapper service instead.',
                    10,
                ],
                [
                    'Code is accessing superglobal variable $_REQUEST in a nested scope. Use a wrapper service instead.',
                    11,
                ],
                [
                    'Code is accessing superglobal variable $_SESSION in a nested scope. Use a wrapper service instead.',
                    12,
                ],
                [
                    'Code is accessing superglobal variable $_COOKIE in a nested scope. Use a wrapper service instead.',
                    13,
                ],
                [
                    'Code is accessing superglobal variable $_FILES in a nested scope. Use a wrapper service instead.',
                    14,
                ],
                [
                    'Code is accessing superglobal variable $_ENV in a nested scope. Use a wrapper service instead.',
                    15,
                ],
                [
                    'Code is accessing superglobal variable $_SERVER in a nested scope. Use a wrapper service instead.',
                    16,
                ],
                [
                    'Code is accessing superglobal variable $GLOBALS in a nested scope. Use a wrapper service instead.',
                    17,
                ],
            ],
        );
    }
}
