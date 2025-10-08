<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverModifySuperGlobalsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverModifySuperGlobalsRule>
 */
class NeverModifySuperGlobalsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverModifySuperGlobalsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/modify-superglobals.php"],
            [
                [
                    'Code is modifying superglobal variable $_GET. Return the new value instead.',
                    5,
                ],
                [
                    'Code is modifying superglobal variable $_POST. Return the new value instead.',
                    6,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST. Return the new value instead.',
                    7,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION. Return the new value instead.',
                    8,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE. Return the new value instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_FILES. Return the new value instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_ENV. Return the new value instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER. Return the new value instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    13,
                ],
            ],
        );
    }
}
