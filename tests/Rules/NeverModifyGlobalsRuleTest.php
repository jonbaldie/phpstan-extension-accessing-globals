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
            ],
        );
    }

    public function testIssue3MutationForms(): void
    {
        $fixture = __DIR__ . "/Data/issue-3-compound-modifications.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying global variable through $GLOBALS[\'plus\']. Use dependency injection instead.',
                    19,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'concat\']. Use dependency injection instead.',
                    20,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'postinc\']. Use dependency injection instead.',
                    21,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'predec\']. Use dependency injection instead.',
                    22,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'preinc\']. Use dependency injection instead.',
                    23,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'postdec\']. Use dependency injection instead.',
                    24,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'coalesce\']. Use dependency injection instead.',
                    25,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'destructured\']. Use dependency injection instead.',
                    26,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'reference\']. Use dependency injection instead.',
                    27,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 9, 'modify.global'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue13ForeachValueTargets(): void
    {
        $fixture = __DIR__ . "/Data/issue-13-foreach-global-targets.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying global variable through $GLOBALS[\'root\']. Use dependency injection instead.',
                    5,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'outer\']. Use dependency injection instead.',
                    8,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'value\']. Use dependency injection instead.',
                    11,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'destructured\']. Use dependency injection instead.',
                    14,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 4, 'modify.global'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue14UnsetGlobalTargets(): void
    {
        $fixture = __DIR__ . "/Data/issue-14-unset-coverage.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying global variable through $GLOBALS[\'root\']. Use dependency injection instead.',
                    5,
                ],
                [
                    'Code is modifying global variable through $GLOBALS[\'outer\']. Use dependency injection instead.',
                    9,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 2, 'modify.global'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }
}
