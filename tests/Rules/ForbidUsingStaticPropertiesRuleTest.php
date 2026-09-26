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
                    'Code is accessing static property AccessingGlobals\Tests\Rules\Data\StaticPropertyConfig::$value. Static properties are global state; pass the value as an argument instead.',
                    16,
                ],
                [
                    'Code is accessing static property AccessingGlobals\Tests\Rules\Data\StaticPropertyConfig::$value. Static properties are global state; pass the value as an argument instead.',
                    26,
                ],
            ],
        );
    }

    public function testDynamicClassOperandIsSkipped(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/dynamic-static-property.php"],
            [],
        );
    }

    public function testThisClassOperandIsReported(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/Issue76Probe.php"],
            [
                [
                    'Code is accessing static property AccessingGlobals\Tests\Rules\Data\Issue76Probe::$prop. Static properties are global state; pass the value as an argument instead.',
                    11,
                ],
            ],
        );
    }

    public function testIssue5RootScopeClosures(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-5-root-scope-closures-opinionated.php"],
            [
                [
                    'Code is accessing static property AccessingGlobals\Tests\Rules\Data\IssueFiveConfig::$prop. Static properties are global state; pass the value as an argument instead.',
                    22,
                ],
            ],
        );
    }
}
