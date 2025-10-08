<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidUsingStaticPropertiesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUsingStaticPropertiesRule>
 */
class ForbidUsingStaticPropertiesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidUsingStaticPropertiesRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/using-static-properties.php"],
            [
                [
                    'Code is accessing static property AccessingGlobals\Tests\Rules\Data\Config::$value. Static properties are a form of global state; inject this dependency instead.',
                    16,
                ],
                [
                    'Code is accessing static property AccessingGlobals\Tests\Rules\Data\Config::$value. Static properties are a form of global state; inject this dependency instead.',
                    26,
                ],
            ],
        );
    }
}
