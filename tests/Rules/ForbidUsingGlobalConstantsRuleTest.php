<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidUsingGlobalConstantsRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidUsingGlobalConstantsRule>
 */
class ForbidUsingGlobalConstantsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidUsingGlobalConstantsRule(self::createReflectionProvider());
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

    public function testIssue23DynamicConstantLookups(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-23-dynamic-constant-lookup.php"],
            [
                [
                    'Code is accessing global constant "ISSUE_TWENTY_THREE_CONST". Pass it as an argument instead to make the dependency explicit.',
                    14,
                ],
                [
                    'Code is calling constant() with a dynamic constant name, which may read a global constant. Pass the value as an argument instead to make the dependency explicit.',
                    21,
                ],
            ],
        );
    }

    public function testIssue23ConstantEdgeCases(): void
    {
        // RuleTestCase analyzes fixtures in isolation, so load the declaration
        // first for ReflectionProvider to resolve the function as the CLI does.
        require_once __DIR__ . "/Data/issue-23-constant-edge-cases.php";

        $this->analyse(
            [__DIR__ . "/Data/issue-23-constant-edge-cases.php"],
            [
                [
                    'Code is accessing global constant "ISSUE_TWENTY_THREE_EDGE_CONST". Pass it as an argument instead to make the dependency explicit.',
                    33,
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
                    'Code is accessing global constant "ISSUE_FIVE_CONST". Pass it as an argument instead to make the dependency explicit.',
                    22,
                ],
            ],
        );
    }
}
