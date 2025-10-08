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
                    'Code is modifying superglobal variable $_GET. Use a wrapper service instead.',
                    5,
                ],
                [
                    'Code is modifying superglobal variable $_POST. Use a wrapper service instead.',
                    6,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST. Use a wrapper service instead.',
                    7,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION. Use a wrapper service instead.',
                    8,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE. Use a wrapper service instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_FILES. Use a wrapper service instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_ENV. Use a wrapper service instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER. Use a wrapper service instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Use a wrapper service instead.',
                    13,
                ],
            ],
        );
    }
}
