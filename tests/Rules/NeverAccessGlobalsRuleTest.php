<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\NeverAccessGlobalsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverAccessGlobalsRule>
 */
class NeverAccessGlobalsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverAccessGlobalsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/data/access-globals.php"],
            [
                [
                    'Code is accessing global variable $foo. Use dependency injection instead.',
                    5,
                ],
                [
                    'Code is accessing global variable $bar. Use dependency injection instead.',
                    10,
                ],
                [
                    'Code is accessing global variable $baz. Use dependency injection instead.',
                    10,
                ],
            ],
        );
    }
}
