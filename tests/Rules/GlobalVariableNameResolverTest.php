<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\GlobalVariableNameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * Observes GlobalVariableNameResolver::resolveGlobalsKey() through a probe
 * rule so that every key is typed by a real PHPStan Scope.
 *
 * @extends RuleTestCase<Rule<Node\Expr\ArrayDimFetch>>
 */
class GlobalVariableNameResolverTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new class implements Rule {
            public function getNodeType(): string
            {
                return Node\Expr\ArrayDimFetch::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                $key = GlobalVariableNameResolver::resolveGlobalsKey($node, $scope);

                return [
                    RuleErrorBuilder::message($key === null ? 'unresolved' : sprintf('key %s', $key))
                        ->identifier('test.globalsKey')
                        ->build(),
                ];
            }
        };
    }

    public function testResolveGlobalsKey(): void
    {
        require_once __DIR__ . '/Data/GlobalVariableNameResolver/globals-keys.php';

        $this->analyse(
            [__DIR__ . '/Data/GlobalVariableNameResolver/globals-keys.php'],
            [
                ['key literal', 7],
                ['key config', 8],
                ['key variable', 10],
                ['unresolved', 11],
                ['unresolved', 12],
                ['unresolved', 13],
                ['unresolved', 14],
            ],
        );
    }
}
