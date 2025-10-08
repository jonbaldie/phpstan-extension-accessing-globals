<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverModifySuperGlobalsInNestedScopeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverModifySuperGlobalsInNestedScopeRule>
 */
class NeverModifySuperGlobalsInNestedScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverModifySuperGlobalsInNestedScopeRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/modify-superglobals-in-nested-scope.php"],
            [
                [
                    'Code is modifying superglobal variable $_GET in a nested scope. Use a wrapper service instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_POST in a nested scope. Use a wrapper service instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST in a nested scope. Use a wrapper service instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Use a wrapper service instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE in a nested scope. Use a wrapper service instead.',
                    13,
                ],
                [
                    'Code is modifying superglobal variable $_FILES in a nested scope. Use a wrapper service instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $_ENV in a nested scope. Use a wrapper service instead.',
                    15,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER in a nested scope. Use a wrapper service instead.',
                    16,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Use a wrapper service instead.',
                    17,
                ],
            ],
        );
    }
}
