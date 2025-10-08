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
        return new ForbidImpureGlobalFunctionsRule();
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
}
