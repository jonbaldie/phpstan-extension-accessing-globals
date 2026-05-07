<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverModifyGlobalsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverModifyGlobalsRule>
 */
class NeverModifyGlobalsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverModifyGlobalsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/modify-globals.php"],
            [
                [
                    'Code is modifying global variable through $GLOBALS[\'foo\']. Use dependency injection instead.',
                    5,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'config\']. Use dependency injection instead.',
                    6,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'count\']. Use dependency injection instead.',
                    7,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'message\']. Use dependency injection instead.',
                    8,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'config\']. Use dependency injection instead.',
                    9,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'config\']. Use dependency injection instead.',
                    10,
                ],
            ],
        );
    }
}
