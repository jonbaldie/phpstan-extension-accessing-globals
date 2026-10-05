<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr>
 */
class ForbidImpureGlobalFunctionsRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    )
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method.
        // Closures and arrow functions defined at file scope have no enclosing
        // function, but their bodies are nested scopes, not root code.
        if ($scope->getFunction() === null && !$scope->isInAnonymousFunction()) {
            return [];
        }

        if ($node instanceof FunctionCallableNode) {
            $name = $node->getName();
        } elseif ($node instanceof FuncCall) {
            $name = $node->name;
        } else {
            return [];
        }

        if (!$name instanceof Name) {
            // This handles dynamic function calls like `$functionName()`.
            // These are a separate problem and not the focus of this rule.
            return [];
        }

        $resolvedFunctionName = $this->reflectionProvider->resolveFunctionName($name, $scope);

        if ($resolvedFunctionName === null) {
            return [];
        }

        if (ImpureFunctionCatalog::isImpure($resolvedFunctionName)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is calling the impure function "%s()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                        $name->toString()
                    )
                )
                    ->identifier('function.impure')
                    ->build(),
            ];
        }

        return [];
    }
}
