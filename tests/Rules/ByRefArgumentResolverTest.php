<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ByRefArgumentResolver;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * Observes ByRefArgumentResolver through a probe rule so that every case runs
 * against a real PHPStan Scope and PHPStan's own walk of the call nodes.
 *
 * @extends RuleTestCase<Rule<Node\Expr>>
 */
class ByRefArgumentResolverTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $resolver = new ByRefArgumentResolver(self::createReflectionProvider());

        return new class ($resolver) implements Rule {
            public function __construct(
                private readonly ByRefArgumentResolver $resolver,
            ) {
            }

            public function getNodeType(): string
            {
                return Node\Expr::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                $printer = new Standard();
                $kind = substr($node::class, strrpos($node::class, '\\') + 1);
                $errors = [];
                foreach ($this->resolver->resolve($node, $scope) as $argument) {
                    $errors[] = RuleErrorBuilder::message(sprintf('%s by-ref %s', $kind, $printer->prettyPrintExpr($argument)))
                        ->identifier('test.byRefArgument')
                        ->build();
                }

                return $errors;
            }
        };
    }

    public function testFunctionCalls(): void
    {
        require_once __DIR__ . '/Data/ByRefArgumentResolver/function-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/ByRefArgumentResolver/function-calls.php'],
            [
                ['FuncCall by-ref $a', 17],
                ['FuncCall by-ref $a', 19],
                ['FuncCall by-ref $b', 20],
                ['FuncCall by-ref $a', 21],
                ['FuncCall by-ref $b', 21],
                ['FuncCall by-ref $c', 21],
                ['FuncCall by-ref $a', 23],
                ['FuncCall by-ref $b', 23],
                ['FuncCall by-ref SORT_DESC', 23],
            ],
        );
    }

    public function testMethodStaticAndConstructorCalls(): void
    {
        require_once __DIR__ . '/Data/ByRefArgumentResolver/method-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/ByRefArgumentResolver/method-calls.php'],
            [
                ['MethodCall by-ref $a', 24],
                ['MethodCall by-ref $b', 25],
                ['MethodCall by-ref $a', 27],
                ['StaticCall by-ref $b', 28],
                ['StaticCall by-ref $a', 29],
                ['New_ by-ref $a', 30],
                ['New_ by-ref $b', 31],
                ['New_ by-ref $a', 32],
            ],
        );
    }

    public function testNullsafeMethodCallsResolveOnceOnTheNullsafeNode(): void
    {
        require_once __DIR__ . '/Data/ByRefArgumentResolver/nullsafe-method-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/ByRefArgumentResolver/nullsafe-method-calls.php'],
            [
                ['NullsafeMethodCall by-ref $a', 16],
                ['NullsafeMethodCall by-ref $b', 17],
                ['NullsafeMethodCall by-ref $a', 18],
            ],
        );
    }
}
