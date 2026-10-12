<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ClassOperandResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\VerbosityLevel;

/**
 * Observes ClassOperandResolver through a probe rule on static calls so that
 * every class operand is resolved against a real PHPStan Scope.
 *
 * @extends RuleTestCase<Rule<StaticCall>>
 */
class ClassOperandResolverTest extends RuleTestCase
{
    private const FIXTURE = __DIR__ . '/Data/ClassOperandResolver/class-operands.php';

    protected function getRule(): Rule
    {
        return new class () implements Rule {
            public function getNodeType(): string
            {
                return StaticCall::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                $type = ClassOperandResolver::resolveType($node->class, $scope);

                return [
                    RuleErrorBuilder::message(sprintf(
                        'name: %s, type: %s',
                        ClassOperandResolver::resolveName($node->class, $scope) ?? 'null',
                        $type?->describe(VerbosityLevel::precise()) ?? 'null',
                    ))
                        ->identifier('test.classOperand')
                        ->build(),
                ];
            }
        };
    }

    public function testClassOperandsResolveToTheirNameAndType(): void
    {
        // RuleTestCase analyzes fixtures in isolation, so load the declarations
        // first for ReflectionProvider to resolve them as the CLI does.
        require_once self::FIXTURE;

        $config = 'ClassOperandResolverFixtures\\Config';
        $child = 'ClassOperandResolverFixtures\\Child';

        $this->analyse(
            [self::FIXTURE],
            [
                // Named classes keep their name as written; their type
                // resolves. `$this` resolves through its type.
                ['name: self, type: $this(' . $child . ')', 23],
                ['name: static, type: static(' . $child . ')', 23],
                ['name: parent, type: $this(' . $config . ')', 23],
                ['name: ' . $child . ', type: $this(' . $child . ')', 23],
                // `Config::`, then typed objects and class-strings, resolve
                // to the object type.
                ['name: ' . $config . ', type: ' . $config, 34],
                ['name: ' . $config . ', type: ' . $config, 34],
                ['name: ' . $config . ', type: ' . $config, 34],
                ['name: ' . $config . ', type: ' . $config, 34],
                ['name: ' . $config . ', type: ' . $config, 34],
                // A union names no single class but still has a type.
                [
                    'name: null, type: ' . $config . '|ClassOperandResolverFixtures\\Other',
                    39,
                ],
                // Operands that name no class resolve to nothing.
                ['name: null, type: null', 44],
                ['name: null, type: null', 44],
                ['name: null, type: null', 44],
            ],
        );
    }
}
