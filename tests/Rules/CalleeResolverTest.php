<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\CalleeResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule<Node\Expr>>
 */
class CalleeResolverTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $resolver = new CalleeResolver(self::createReflectionProvider());

        return new class ($resolver) implements Rule {
            public function __construct(
                private readonly CalleeResolver $resolver,
            ) {
            }

            public function getNodeType(): string
            {
                return Node\Expr::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                if (!$node instanceof FuncCall && !$node instanceof FunctionCallableNode) {
                    return [];
                }

                $function = $this->resolver->resolveFunction($node, $scope);
                if ($function === null) {
                    return [];
                }

                return [
                    RuleErrorBuilder::message(sprintf('resolved %s', $function->getName()))
                        ->identifier('test.resolvedCallee')
                        ->build(),
                ];
            }
        };
    }

    public function testNamedCallableStringAndFirstClassFunctionCallsResolve(): void
    {
        require_once __DIR__ . '/Data/CalleeResolver/function-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/CalleeResolver/function-calls.php'],
            [
                ['resolved time', 7],
                ['resolved time', 10],
                ['resolved time', 12],
            ],
        );
    }
}
