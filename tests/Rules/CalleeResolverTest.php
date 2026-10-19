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
    private bool $observeParameters = false;

    protected function getRule(): Rule
    {
        $resolver = new CalleeResolver(self::createReflectionProvider());

        return new class ($resolver, $this->observeParameters) implements Rule {
            public function __construct(
                private readonly CalleeResolver $resolver,
                private readonly bool $observeParameters,
            ) {
            }

            public function getNodeType(): string
            {
                return Node\Expr::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                if ($this->observeParameters) {
                    if (!$node instanceof Node\Expr\CallLike
                        || ($node instanceof Node\Expr\MethodCall && $node->hasAttribute('virtualNullsafeMethodCall'))
                    ) {
                        return [];
                    }

                    $variants = $this->resolver->getParameterAcceptors($node, $scope);
                    $signatures = [];
                    foreach ($variants as $variant) {
                        $parameters = [];
                        foreach ($variant->getParameters() as $parameter) {
                            $parameters[] = ($parameter->passedByReference()->yes() ? '&' : '') . $parameter->getName();
                        }
                        $signatures[] = implode(', ', $parameters);
                    }

                    return [
                        RuleErrorBuilder::message('parameters: ' . ($signatures === [] ? 'unresolved' : implode(' | ', $signatures)))
                            ->identifier('test.parameterAcceptors')
                            ->build(),
                    ];
                }

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

    public function testMethodAndNullsafeCallsExposeTheirParameterAcceptors(): void
    {
        $this->observeParameters = true;
        require_once __DIR__ . '/Data/CalleeResolver/method-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/CalleeResolver/method-calls.php'],
            [
                ['parameters: &value, count', 16],
                ['parameters: &value, count', 18],
                ['parameters: &value, count', 19],
                ['parameters: &value, count', 20],
                ['parameters: unresolved', 21],
                ['parameters: unresolved', 22],
            ],
        );
    }

    public function testStaticCallsExposeTheirParameterAcceptors(): void
    {
        $this->observeParameters = true;
        require_once __DIR__ . '/Data/CalleeResolver/static-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/CalleeResolver/static-calls.php'],
            [
                ['parameters: &value, count', 17],
                ['parameters: &value, count', 18],
                ['parameters: &value, count', 19],
                ['parameters: &value, count', 20],
                ['parameters: unresolved', 21],
                ['parameters: unresolved', 22],
                ['parameters: unresolved', 24],
            ],
        );
    }

    public function testConstructorCallsExposeTheirParameterAcceptors(): void
    {
        $this->observeParameters = true;
        require_once __DIR__ . '/Data/CalleeResolver/constructor-calls.php';

        $this->analyse(
            [__DIR__ . '/Data/CalleeResolver/constructor-calls.php'],
            [
                ['parameters: &value, count', 20],
                ['parameters: &value, count', 21],
                ['parameters: &value, count', 22],
                ['parameters: &anonymousValue', 23],
                ['parameters: unresolved', 28],
                ['parameters: unresolved', 30],
            ],
        );
    }

    public function testFunctionsAndCallableExpressionsExposeTheirParameterAcceptors(): void
    {
        $this->observeParameters = true;
        require_once __DIR__ . '/Data/CalleeResolver/parameter-functions.php';

        $this->analyse(
            [__DIR__ . '/Data/CalleeResolver/parameter-functions.php'],
            [
                ['parameters: &value, count', 19],
                ['parameters: &value, count', 21],
                ['parameters: &closedValue', 23],
                ['parameters: &invokedValue', 24],
                ['parameters: unresolved', 25],
                ['parameters: unresolved', 27],
            ],
        );
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
