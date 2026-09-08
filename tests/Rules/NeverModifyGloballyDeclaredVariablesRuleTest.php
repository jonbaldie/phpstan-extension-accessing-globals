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
}
