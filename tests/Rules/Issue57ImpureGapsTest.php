<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ForbidImpureGlobalFunctionsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidImpureGlobalFunctionsRule>
 */
class Issue57ImpureGapsTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidImpureGlobalFunctionsRule(self::createReflectionProvider());
    }

    public function testSameCategoryFunctionsAreReported(): void
    {
        $this->analyse(
            [__DIR__ . "/Data/issue-57-missing-impure-functions.php"],
            [
                [
                    'Code is calling the impure function "time()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    9,
                ],
                [
                    'Code is calling the impure function "hrtime()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    14,
                ],
                [
                    'Code is calling the impure function "getcwd()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    19,
                ],
                [
                    'Code is calling the impure function "glob()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    24,
                ],
                [
                    'Code is calling the impure function "scandir()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    29,
                ],
                [
                    'Code is calling the impure function "error_get_last()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    34,
                ],
                [
                    'Code is calling the impure function "php_sapi_name()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    39,
                ],
                [
                    'Code is calling the impure function "ini_get()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    44,
                ],
            ],
        );
    }
}
