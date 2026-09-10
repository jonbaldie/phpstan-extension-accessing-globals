<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverModifyGloballyDeclaredVariablesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverModifyGloballyDeclaredVariablesRule>
 */
class NeverModifyGloballyDeclaredVariablesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverModifyGloballyDeclaredVariablesRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/modify-globals.php"],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    11,
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
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    34,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    35,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    36,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    37,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    38,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    39,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    40,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    41,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    42,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 9, 'modify.global'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue4NestedShadowingAndByRefCapture(): void
    {
        $fixture = __DIR__ . "/Data/issue-4-nested-shadowing.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    16,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    19,
                ],
            ],
        );

        $this->assertSame(
            ['modify.global', 'modify.global'],
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testRuleClosureDeclaresOwnGlobal(): void
    {
        // https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/2
        // A closure declaring its own `global $db` must produce exactly one
        // modify.global error, not one per enclosing function-like scope.
        $this->analyse(
            [__DIR__ . "/Data/modify-globals-in-closure.php"],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    7,
                ],
            ],
        );
    }

    public function testIssue13ForeachByReferenceValueTarget(): void
    {
        $fixture = __DIR__ . "/Data/issue-13-foreach-global-binding.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    9,
                ],
            ],
        );

        $this->assertSame(
            ['modify.global'],
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }
}
