<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidUsingClassConstantsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUsingClassConstantsRule>
 */
class ForbidUsingClassConstantsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidUsingClassConstantsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/data/using-class-constants.php"],
            [
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\AnotherConfig::RETRIES. This creates a hidden dependency; pass the value as an argument instead.",
                    27,
                ],
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    45,
                ],
            ],
        );
    }
}
