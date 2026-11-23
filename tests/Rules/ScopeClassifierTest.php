<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ScopeClassifier;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<Rule<Node\Expr\Variable>> */
class ScopeClassifierTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new class implements Rule {
            public function getNodeType(): string
            {
                return Node\Expr\Variable::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                if (!in_array($node->name, ['root', 'function', 'method', 'insideClosure', 'insideArrow'], true)) {
                    return [];
                }
                return [RuleErrorBuilder::message(ScopeClassifier::isRootScope($scope) ? 'root' : 'nested')
                    ->identifier('test.scope')->build()];
            }
        };
    }

    public function testScopes(): void
    {
        $this->analyse([__DIR__ . '/Data/ScopeClassifier/scopes.php'], [
            ['root', 3],
            ['nested', 4],
            ['nested', 5],
            ['nested', 6],
            ['nested', 7],
        ]);
    }
}
