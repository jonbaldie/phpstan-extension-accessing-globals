<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidImpureGlobalFunctionsRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidImpureGlobalFunctionsRule>
 */
class ForbidImpureGlobalFunctionsRuleTest extends RuleTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/forbid-impure-global-functions-rule-test.neon'];
    }

    protected function getRule(): Rule
    {
        return new ForbidImpureGlobalFunctionsRule(self::getContainer()->getByType(ReflectionProvider::class));
    }

    public function testFlagsBuiltinImpureFunctions(): void
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

    public function testFlagsBuiltinImpureFunctionAliases(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/using-impure-function-aliases.php'],
            [
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    11,
                ],
                [
                    'Code is calling the impure function "getenv()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    12,
                ],
            ],
        );
    }

    public function testFlagsBuiltinNamespaceFallbackCalls(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/using-impure-functions-with-namespace-fallback.php'],
            [
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    8,
                ],
                [
                    'Code is calling the impure function "getenv()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    9,
                ],
            ],
        );
    }

    public function testAllowsNamespacedShadowedFunctions(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/using-namespaced-shadowed-functions.php'],
            [],
        );
    }

    public function testAllowsLocallyDefinedOverrides(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/using-impure-functions-with-local-override.php'],
            [],
        );
    }
}
