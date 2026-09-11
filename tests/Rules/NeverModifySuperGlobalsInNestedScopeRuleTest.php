<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\MutationTargetResolver;
use AccessingGlobals\Rules\NeverModifySuperGlobalsInNestedScopeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverModifySuperGlobalsInNestedScopeRule>
 */
class NeverModifySuperGlobalsInNestedScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverModifySuperGlobalsInNestedScopeRule(
            new MutationTargetResolver(self::createReflectionProvider()),
        );
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/modify-superglobals-in-nested-scope.php"],
            [
                [
                    'Code is modifying superglobal variable $_GET in a nested scope. Return the new value instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_POST in a nested scope. Return the new value instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST in a nested scope. Return the new value instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Return the new value instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE in a nested scope. Return the new value instead.',
                    13,
                ],
                [
                    'Code is modifying superglobal variable $_FILES in a nested scope. Return the new value instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $_ENV in a nested scope. Return the new value instead.',
                    15,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER in a nested scope. Return the new value instead.',
                    16,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Return the new value instead.',
                    17,
                ],
            ],
        );
    }

    public function testIssue36LiteralDynamicNames(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-36-literal-dynamic-superglobals.php"],
            [
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Return the new value instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Return the new value instead.',
                    17,
                ],
            ],
        );
    }

    public function testIssue3MutationFormsOnlyInNestedScope(): void
    {
        $fixture = __DIR__ . "/Data/issue-3-nested-syntax-coverage.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_GET in a nested scope. Return the new value instead.',
                    8,
                ],
                [
                    'Code is modifying superglobal variable $_POST in a nested scope. Return the new value instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST in a nested scope. Return the new value instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Return the new value instead.',
                    11,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE in a nested scope. Return the new value instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $_FILES in a nested scope. Return the new value instead.',
                    13,
                ],
                [
                    'Code is modifying superglobal variable $_ENV in a nested scope. Return the new value instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER in a nested scope. Return the new value instead.',
                    15,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Return the new value instead.',
                    16,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 9, 'modify.superglobal.nested'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue5RootScopeClosures(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-5-root-scope-closures-modify.php"],
            [
                [
                    'Code is modifying superglobal variable $_COOKIE in a nested scope. Return the new value instead.',
                    10,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER in a nested scope. Return the new value instead.',
                    14,
                ],
            ],
        );
    }

    public function testIssue13ForeachValueTargetsOnlyInNestedScope(): void
    {
        $fixture = __DIR__ . "/Data/issue-13-foreach-superglobals.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_ENV in a nested scope. Return the new value instead.',
                    19,
                ],
                [
                    'Code is modifying superglobal variable $_FILES in a nested scope. Return the new value instead.',
                    19,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 2, 'modify.superglobal.nested'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue24ByReferenceForeachTargetsOnlyInNestedScope(): void
    {
        $fixture = __DIR__ . "/Data/issue-24-by-reference-foreach.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Return the new value instead.',
                    7,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Return the new value instead.',
                    14,
                ],
                [
                    'Code is modifying superglobal variable $_SERVER in a nested scope. Return the new value instead.',
                    21,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 3, 'modify.superglobal.nested'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue14UnsetSuperglobalKey(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-14-unset.php"],
            [
                [
                    'Code is modifying superglobal variable $_GET in a nested scope. Return the new value instead.',
                    5,
                ],
            ],
        );
    }

    public function testIssue25ByReferenceBuiltinsOnlyInNestedScope(): void
    {
        $fixture = __DIR__ . "/Data/issue-25-by-reference-builtins.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Return the new value instead.',
                    7,
                ],
                [
                    'Code is modifying superglobal variable $_GET in a nested scope. Return the new value instead.',
                    12,
                ],
                [
                    'Code is modifying superglobal variable $_POST in a nested scope. Return the new value instead.',
                    17,
                ],
                [
                    'Code is modifying superglobal variable $_COOKIE in a nested scope. Return the new value instead.',
                    22,
                ],
                [
                    'Code is modifying superglobal variable $_REQUEST in a nested scope. Return the new value instead.',
                    27,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Return the new value instead.',
                    32,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 6, 'modify.superglobal.nested'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue14UnsetTargetsInNestedScope(): void
    {
        $fixture = __DIR__ . "/Data/issue-14-unset-coverage.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_POST in a nested scope. Return the new value instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $_SESSION in a nested scope. Return the new value instead.',
                    9,
                ],
                [
                    'Code is modifying superglobal variable $GLOBALS in a nested scope. Return the new value instead.',
                    9,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 3, 'modify.superglobal.nested'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue33ForeachKeyTargetsOnlyInNestedScope(): void
    {
        $fixture = __DIR__ . "/Data/issue-33-foreach-key-targets.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying superglobal variable $_POST in a nested scope. Return the new value instead.',
                    20,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 1, 'modify.superglobal.nested'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }
}
