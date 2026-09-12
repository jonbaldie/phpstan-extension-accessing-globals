<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\MutationTargetResolver;
use AccessingGlobals\Rules\NeverModifyGloballyDeclaredVariablesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NeverModifyGloballyDeclaredVariablesRule>
 */
class NeverModifyGloballyDeclaredVariablesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NeverModifyGloballyDeclaredVariablesRule(
            new MutationTargetResolver(self::createReflectionProvider()),
        );
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/modify-globals.php"],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    11,
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
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    34,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    35,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    36,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    37,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    38,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    39,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    40,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    41,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    42,
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

    public function testIssue15DimensionMutationForms(): void
    {
        $fixture = __DIR__ . "/Data/issue-15-dimension-writes.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    7,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    8,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    9,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    10,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    11,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    12,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    13,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    14,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    15,
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

    public function testIssue4NestedShadowingAndByRefCapture(): void
    {
        $fixture = __DIR__ . "/Data/issue-4-nested-shadowing.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    16,
                ],
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    19,
                ],
            ],
        );

        $this->assertSame(
            ['modify.global', 'modify.global'],
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testRuleClosureDeclaresOwnGlobal(): void
    {
        // https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/2
        // A closure declaring its own `global $db` must produce exactly one
        // modify.global error, not one per enclosing function-like scope.
        $this->analyse(
            [__DIR__ . "/Data/modify-globals-in-closure.php"],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    7,
                ],
            ],
        );
    }

    public function testIssue13ForeachByReferenceValueTarget(): void
    {
        $fixture = __DIR__ . "/Data/issue-13-foreach-global-binding.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    9,
                ],
            ],
        );

        $this->assertSame(
            ['modify.global'],
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue14UnsetGlobalBindingIsNotMutation(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-14-unset-global-binding.php"],
            [],
        );
    }

    public function testIssue16LiteralDynamicGlobalDeclaration(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-16-literal-dynamic-global.php"],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    6,
                ],
            ],
        );
    }

    public function testIssue16RuntimeDynamicGlobalDeclaration(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-16-runtime-dynamic-global.php"],
            [
                [
                    'Code is modifying a variable with a dynamic name that was declared with the "global" keyword. Use dependency injection instead.',
                    6,
                ],
            ],
        );
    }

    public function testIssue16ConstantDynamicGlobalDeclaration(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-16-constant-dynamic-global.php"],
            [
                [
                    'Code is modifying variable $db that was declared with the "global" keyword. Use dependency injection instead.',
                    8,
                ],
            ],
        );
    }

    public function testIssue22ObjectPropertyMutationForms(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-22-property-mutation.php"],
            [
                [
                    'Code is modifying variable $box that was declared with the "global" keyword. Use dependency injection instead.',
                    7,
                ],
                [
                    'Code is modifying variable $box that was declared with the "global" keyword. Use dependency injection instead.',
                    8,
                ],
                [
                    'Code is modifying variable $box that was declared with the "global" keyword. Use dependency injection instead.',
                    9,
                ],
            ],
        );
    }

    public function testIssue25ByReferenceBuiltinOnGlobalKeyword(): void
    {
        $fixture = __DIR__ . "/Data/issue-25-global-keyword-by-reference.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $items that was declared with the "global" keyword. Use dependency injection instead.',
                    9,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 1, 'modify.global'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue33ForeachKeyTargetGlobalBinding(): void
    {
        $fixture = __DIR__ . "/Data/issue-33-foreach-key-targets.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $globalDeclared that was declared with the "global" keyword. Use dependency injection instead.',
                    17,
                ],
            ],
        );

        $this->assertSame(
            array_fill(0, 1, 'modify.global'),
            array_map(
                static fn(\PHPStan\Analyser\Error $error): ?string => $error->getIdentifier(),
                $this->gatherAnalyserErrors([$fixture]),
            ),
        );
    }

    public function testIssue43MethodByReferenceGlobalKeyword(): void
    {
        require_once __DIR__ . "/Data/issue-43-method-by-reference.php";
        $fixture = __DIR__ . "/Data/issue-43-method-by-reference.php";

        $this->analyse(
            [$fixture],
            [
                [
                    'Code is modifying variable $appData that was declared with the "global" keyword. Use dependency injection instead.',
                    66,
                ],
                [
                    'Code is modifying variable $appData that was declared with the "global" keyword. Use dependency injection instead.',
                    67,
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

    public function testIssue47NestedScopePropertyMutations(): void
    {
        // https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/47
        // Arrow functions and by-value `use` captures still share the object
        // handle, so property writes inside them mutate the global object.
        $fixture = __DIR__ . "/Data/issue-47-nested-scope-property-mutation.php";

        $message = 'Code is modifying variable $state that was declared with the "global" keyword. Use dependency injection instead.';

        $this->analyse(
            [$fixture],
            [
                [$message, 13],
                [$message, 22],
                [$message, 23],
                [$message, 24],
                [$message, 55],
            ],
        );
    }
}
