<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\StaticPropertyAccessResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * Observes StaticPropertyAccessResolver through a probe rule so that every
 * case runs against a real PHPStan Scope, including type inference for class
 * and property name expressions.
 *
 * @extends RuleTestCase<Rule<StaticPropertyFetch>>
 */
class StaticPropertyAccessResolverTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new class (new StaticPropertyAccessResolver()) implements Rule {
            public function __construct(
                private readonly StaticPropertyAccessResolver $resolver,
            ) {
            }

            public function getNodeType(): string
            {
                return StaticPropertyFetch::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                $access = $this->resolver->resolve($node, $scope);

                if ($access === null) {
                    return [];
                }

                return [
                    RuleErrorBuilder::message(sprintf('%s :: %s', $access->className, $access->propertyName))
                        ->identifier('test.staticPropertyAccess')
                        ->build(),
                ];
            }
        };
    }

    public function testNamedClasses(): void
    {
        // RuleTestCase analyzes fixtures in isolation, so load the declarations
        // first for ReflectionProvider to resolve them as the CLI does.
        require_once __DIR__ . '/Data/StaticPropertyAccessResolver/named-classes.php';

        $this->analyse(
            [__DIR__ . '/Data/StaticPropertyAccessResolver/named-classes.php'],
            [
                ['StaticPropertyAccessResolverFixtures\\Config :: value', 12],
                ['StaticPropertyAccessResolverFixtures\\Child :: value', 19],
                ['StaticPropertyAccessResolverFixtures\\Child :: value', 19],
                ['StaticPropertyAccessResolverFixtures\\Config :: value', 19],
            ],
        );
    }

    public function testClassExpressionsResolveThroughTheirType(): void
    {
        require_once __DIR__ . '/Data/StaticPropertyAccessResolver/class-expressions.php';

        $this->analyse(
            [__DIR__ . '/Data/StaticPropertyAccessResolver/class-expressions.php'],
            [
                ['StaticPropertyAccessResolverFixtures\\Expressions\\Config :: value', 11],
                ['StaticPropertyAccessResolverFixtures\\Expressions\\Config :: value', 25],
                ['StaticPropertyAccessResolverFixtures\\Expressions\\Config :: value', 25],
                ['StaticPropertyAccessResolverFixtures\\Expressions\\Config :: value', 25],
            ],
        );
    }

    public function testPropertyNameExpressionsResolveThroughConstantStrings(): void
    {
        require_once __DIR__ . '/Data/StaticPropertyAccessResolver/property-name-expressions.php';

        $this->analyse(
            [__DIR__ . '/Data/StaticPropertyAccessResolver/property-name-expressions.php'],
            [
                ['StaticPropertyAccessResolverFixtures\\PropertyNames\\Config :: value', 15],
                ['StaticPropertyAccessResolverFixtures\\PropertyNames\\Config :: value', 15],
                ['StaticPropertyAccessResolverFixtures\\PropertyNames\\Config :: {expression}', 15],
                ['StaticPropertyAccessResolverFixtures\\PropertyNames\\Config :: {expression}', 15],
            ],
        );
    }
}
