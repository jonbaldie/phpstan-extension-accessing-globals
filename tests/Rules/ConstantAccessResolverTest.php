<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ClassConstantAccess;
use AccessingGlobals\Rules\ConstantAccess;
use AccessingGlobals\Rules\ConstantAccessResolver;
use AccessingGlobals\Rules\DynamicConstantAccess;
use AccessingGlobals\Rules\GlobalConstantAccess;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * Observes ConstantAccessResolver through a probe rule so that every case runs
 * against a real PHPStan Scope, including namespace-aware function resolution.
 *
 * @extends RuleTestCase<Rule<Node\Expr>>
 */
class ConstantAccessResolverTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $resolver = new ConstantAccessResolver(self::createReflectionProvider());

        return new class ($resolver) implements Rule {
            public function __construct(
                private readonly ConstantAccessResolver $resolver,
            ) {
            }

            public function getNodeType(): string
            {
                return Node\Expr::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                $access = $this->resolver->resolve($node, $scope);

                if ($access === null) {
                    return [];
                }

                return [
                    RuleErrorBuilder::message(self::describe($access))
                        ->identifier('test.constantAccess')
                        ->build(),
                ];
            }

            private static function describe(ConstantAccess $access): string
            {
                return match (true) {
                    $access instanceof GlobalConstantAccess => sprintf('global %s', $access->constantName),
                    $access instanceof ClassConstantAccess => sprintf('class %s :: %s', $access->className, $access->constantName),
                    $access instanceof DynamicConstantAccess => 'dynamic',
                };
            }
        };
    }

    public function testConstantFunctionCalls(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/ConstantAccessResolver/literals.php'],
            [
                ['global FOO', 7],
                ['global \\Ns\\FOO', 19],
                ['class Foo :: BAR', 20],
                ['class Ns\\Foo :: BAR', 21],
                ['dynamic', 26],
                ['global FOO', 32],
                ['class Foo :: BAR', 34],
                ['class Foo :: BAR', 35],
                ['dynamic', 37],
                ['dynamic', 39],
            ],
        );
    }

    public function testDirectFetches(): void
    {
        require_once __DIR__ . '/Data/ConstantAccessResolver/direct.php';

        $this->analyse(
            [__DIR__ . '/Data/ConstantAccessResolver/direct.php'],
            [
                ['global SETTING', 12],
                ['class ResolverDirect\\External :: LIMIT', 13],
            ],
        );
    }

    public function testSharedClassExclusionsAndResolvedOperands(): void
    {
        require_once __DIR__ . '/Data/ConstantAccessResolver/exclusions.php';

        $this->analyse(
            [__DIR__ . '/Data/ConstantAccessResolver/exclusions.php'],
            [
                ['class ResolverExclusions\\External :: LIMIT', 37],
                ['class ResolverExclusions\\External :: LIMIT', 38],
                ['class ResolverExclusions\\External :: LIMIT', 39],
                ['class ResolverExclusions\\Priority :: LIMIT', 40],
                ['class ResolverExclusions\\Priority :: LIMIT', 41],
            ],
        );
    }

    public function testNamespacedCallsResolveThroughTheScope(): void
    {
        // RuleTestCase analyzes fixtures in isolation, so load the declaration
        // first for ReflectionProvider to resolve the function as the CLI does.
        require_once __DIR__ . "/Data/ConstantAccessResolver/namespaced.php";

        $this->analyse(
            [__DIR__ . '/Data/ConstantAccessResolver/namespaced.php'],
            [
                ['global FOO', 20],
            ],
        );
    }
}
