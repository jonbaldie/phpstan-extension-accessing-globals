<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidUsingClassConstantsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUsingClassConstantsRule>
 */
class ForbidUsingClassConstantsRuleIssue46Test extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidUsingClassConstantsRule(self::createReflectionProvider());
    }

    public function testEnumCasesAreNotForbiddenClassConstants(): void
    {
        $this->analyse(
            [
                __DIR__ . "/Data/Issue46/Priority.php",
                __DIR__ . "/Data/Issue46/Task.php",
                __DIR__ . "/Data/Issue46/EnumCaseViaConstant.php",
            ],
            [
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\Issue46\Priority::DEFAULT_PRIORITY. This creates a hidden dependency; pass the value as an argument instead.",
                    24,
                ],
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\Issue46\Priority::DEFAULT_PRIORITY. This creates a hidden dependency; pass the value as an argument instead.",
                    17,
                ],
            ],
        );
    }
}
