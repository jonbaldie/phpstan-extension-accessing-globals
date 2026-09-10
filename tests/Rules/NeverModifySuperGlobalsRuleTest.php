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

    public function testIssue3MutationForms(): void
    {
        $fixture = __DIR__ . "/Data/issue-3-compound-modifications.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_SESSION. Return the new value instead.',
                    5,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION. Return the new value instead.',
                    6,
                ],
                [
                    'Code is modifying superglobal variable $_GET. Return the new value instead.',
                    7,
                ],
                [
                    'Code is modifying superglobal variable $_POST. Return the new value instead.',
                    8,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST. Return the new value instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_FILES. Return the new value instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE. Return the new value instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER. Return the new value instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER. Return the new value instead.',
                    13,
                ],
                [
                    'Code is modifying superglobal variable $_ENV. Return the new value instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    19,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    20,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    21,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    22,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    23,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    24,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    25,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    26,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS. Return the new value instead.',
                    27,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 19, 'modify.superglobal'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue13ForeachValueTarget(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-13-foreach-value-target.php"],
            [
                [
                    'Code is modifying superglobal variable $_GET. Return the new value instead.',
                    5,
                ],
            ],
        );
    }

    public function testIssue13ForeachValueTargets(): void
    {
        $fixture = __DIR__ . "/Data/issue-13-foreach-superglobals.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_GET. Return the new value instead.',
                    5,
                ],
                [
                    'Code is modifying superglobal variable $_POST. Return the new value instead.',
                    8,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST. Return the new value instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE. Return the new value instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION. Return the new value instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $_ENV. Return the new value instead.',
                    19,
                ],
                [
                    'Code is modifying superglobal variable $_FILES. Return the new value instead.',
                    19,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 7, 'modify.superglobal'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }
}
