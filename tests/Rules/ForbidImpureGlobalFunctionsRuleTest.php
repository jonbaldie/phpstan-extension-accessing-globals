<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidImpureGlobalFunctionsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidImpureGlobalFunctionsRule>
 */
class ForbidImpureGlobalFunctionsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidImpureGlobalFunctionsRule(self::createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/using-impure-functions.php"],
            [
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    12,
                ],
                [
                    'Code is calling the impure function "rand()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    13,
                ],
                [
                    'Code is calling the impure function "getenv()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    14,
                ],
                [
                    'Code is calling the impure function "file_get_contents()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    23,
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
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    22,
                ],
                [
                    'Code is calling the impure function "rand()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    26,
                ],
            ],
        );
    }

    public function testIssue21NamespacedFunctionShadow(): void
    {
        // RuleTestCase analyzes fixtures in isolation, so load the declaration
        // first for ReflectionProvider to resolve the function as the CLI does.
        require_once __DIR__ . "/Data/issue-21-namespaced-function-shadow.php";

        $this->analyse(
            [__DIR__ . "/Data/issue-21-namespaced-function-shadow.php"],
            [],
        );
    }

    public function testIssue45FirstClassCallables(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-45-first-class-callables.php"],
            [
                [
                    'Code is calling the impure function "file_get_contents()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    9,
                ],
                [
                    'Code is calling the impure function "rand()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    14,
                ],
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    21,
                ],
                [
                    'Code is calling the impure function "getenv()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    26,
                ],
            ],
        );
    }

    public function testIssue21NamespacedFunctionResolution(): void
    {
        // RuleTestCase analyzes fixtures in isolation, so load the declaration
        // first for ReflectionProvider to resolve the function as the CLI does.
        require_once __DIR__ . "/Data/issue-21-function-resolution.php";

        $this->analyse(
            [__DIR__ . "/Data/issue-21-function-resolution.php"],
            [
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    21,
                ],
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    26,
                ],
            ],
        );
    }
}
