<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverAccessSuperGlobalsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverAccessSuperGlobalsRule>
 */
class NeverAccessSuperGlobalsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverAccessSuperGlobalsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/access-superglobals.php"],
            [
                [
                    'Code is accessing superglobal variable $_GET. Pass the value as an argument instead.',
                    5,
                ],
                [
                    'Code is accessing superglobal variable $_POST. Pass the value as an argument instead.',
                    6,
                ],
                [
                    'Code is accessing superglobal variable $_REQUEST. Pass the value as an argument instead.',
                    7,
                ],
                [
                    'Code is accessing superglobal variable $_SESSION. Pass the value as an argument instead.',
                    8,
                ],
                [
                    'Code is accessing superglobal variable $_COOKIE. Pass the value as an argument instead.',
                    9,
                ],
                [
                    'Code is accessing superglobal variable $_FILES. Pass the value as an argument instead.',
                    10,
                ],
                [
                    'Code is accessing superglobal variable $_ENV. Pass the value as an argument instead.',
                    11,
                ],
                [
                    'Code is accessing superglobal variable $_SERVER. Pass the value as an argument instead.',
                    12,
                ],
                [
                    'Code is accessing superglobal variable $GLOBALS. Pass the value as an argument instead.',
                    13,
                ],
            ],
        );
    }
}
