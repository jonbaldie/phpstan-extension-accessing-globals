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
}
