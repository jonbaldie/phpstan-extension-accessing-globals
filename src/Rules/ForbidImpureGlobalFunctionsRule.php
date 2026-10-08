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
    private readonly CalleeResolver $calleeResolver;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->calleeResolver = new CalleeResolver($reflectionProvider);
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

        if (!$node instanceof FunctionCallableNode && !$node instanceof FuncCall) {
            return [];
        }

        $function = $this->calleeResolver->resolveFunction($node, $scope);
        if ($function === null || !ImpureFunctionCatalog::isImpure($function->getName())) {
            return [];
        }

        $name = $node instanceof FuncCall ? $node->name : $node->getName();
        $displayName = $name instanceof Name ? $name->toString() : $function->getName();

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Code is calling the impure function "%s()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                    $displayName
                )
            )
                ->identifier('function.impure')
                ->build(),
        ];
    }
}
