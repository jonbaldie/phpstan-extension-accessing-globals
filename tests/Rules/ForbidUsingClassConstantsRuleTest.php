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
        return new ForbidUsingClassConstantsRule(self::createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/using-class-constants.php"],
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

    public function testIssue5RootScopeClosures(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-5-root-scope-closures-opinionated.php"],
            [
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\IssueFiveConfig::BAR. This creates a hidden dependency; pass the value as an argument instead.",
                    22,
                ],
            ],
        );
    }

    public function testIssue129ResolvedClassOperands(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-129-class-constant-operands.php"],
            [
                [
                    "Code is accessing constant AccessingGlobals\\Tests\\Rules\\Data\\Issue129\\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    7,
                ],
                [
                    "Code is accessing constant AccessingGlobals\\Tests\\Rules\\Data\\Issue129\\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    13,
                ],
            ],
        );
    }

    public function testIssue129OperandPolicyAndScopes(): void
    {
        $message = "Code is accessing constant AccessingGlobals\\Tests\\Rules\\Data\\Issue129\\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.";

        $this->analyse(
            [
                __DIR__ . "/Data/Issue129/Client.php",
                __DIR__ . "/Data/Issue129/Child.php",
            ],
            [
                [$message, 13],
                [$message, 14],
                [$message, 15],
                [$message, 22],
                [$message, 71],
                [$message, 75],
                [$message, 77],
            ],
        );
    }

    public function testDynamicClassConstantIsSkipped(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/dynamic-class-constant.php"],
            [],
        );
    }

    public function testIssue35ClassConstantViaConstant(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-35-constant-class-lookup.php"],
            [
                [
                    "Code is accessing constant Client::OWN_TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    27,
                ],
                [
                    "Code is accessing constant Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    39,
                ],
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\Issue35\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    42,
                ],
                [
                    "Code is accessing constant Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    51,
                ],
                [
                    "Code is accessing constant AccessingGlobals\Tests\Rules\Data\Issue35\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    54,
                ],
            ],
        );
    }

    public function testIssue44ClassConstantViaConstantInNamespace(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-44-constant-class-lookup-namespaced.php"],
            [
                [
                    "Code is accessing constant Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    26,
                ],
            ],
        );
    }

    public function testIssue125KnownClassConstantNamesViaConstant(): void
    {
        $this->analyse(
            [
                __DIR__ . "/Data/Issue125/Config.php",
                __DIR__ . "/Data/Issue125/Priority.php",
                __DIR__ . "/Data/Issue125/known-constant-names.php",
            ],
            [
                [
                    "Code is accessing constant AccessingGlobals\\Tests\\Rules\\Data\\Issue125\\Config::TIMEOUT. This creates a hidden dependency; pass the value as an argument instead.",
                    31,
                ],
            ],
        );
    }
}
