<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidUsingGlobalConstantsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUsingGlobalConstantsRule>
 */
class ForbidUsingGlobalConstantsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidUsingGlobalConstantsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/using-global-constants.php"],
            [
                [
                    'Code is accessing global constant "MY_CONSTANT". Pass it as an argument instead to make the dependency explicit.',
                    18,
                ],
                [
                    'Code is accessing global constant "ANOTHER_CONSTANT". Pass it as an argument instead to make the dependency explicit.',
                    19,
                ],
                [
                    'Code is accessing global constant "MY_CONSTANT". Pass it as an argument instead to make the dependency explicit.',
                    27,
                ],
            ],
        );
    }
}
