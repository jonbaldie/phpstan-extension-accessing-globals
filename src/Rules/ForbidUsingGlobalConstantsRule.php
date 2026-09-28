<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr>
 */
class ForbidUsingGlobalConstantsRule implements Rule
{
    private readonly ConstantAccessResolver $constantAccessResolver;

    public function __construct(
        ReflectionProvider $reflectionProvider,
    )
    {
        $this->constantAccessResolver = new ConstantAccessResolver($reflectionProvider);
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

        if ($node instanceof Node\Expr\ConstFetch) {
            return $this->processConstFetch($node);
        }

        if ($node instanceof FuncCall) {
            return $this->processConstantFunctionCall($node, $scope);
        }

        return [];
    }

    private function processConstFetch(Node\Expr\ConstFetch $node): array
    {
        $constantName = $node->name->toString();
        $lowerCaseConstantName = strtolower($constantName);

        // Ignore PHP's built-in pseudo-constants
        if (in_array($lowerCaseConstantName, ['true', 'false', 'null'], true)) {
            return [];
        }

        // If we're here, it's a user-defined global constant.
        // This is a hidden dependency and should be passed as an argument instead.
        return [
            $this->buildGlobalConstantError($constantName),
        ];
    }

    /**
     * constant() can read a global constant without a ConstFetch node, e.g.
     * constant('MY_CONSTANT'). The name may even be fully dynamic, so flag
     * conservatively whenever a builtin constant() lookup happens.
     */
    private function processConstantFunctionCall(FuncCall $node, Scope $scope): array
    {
        $access = $this->constantAccessResolver->resolveConstantFunctionCall($node, $scope);

        if ($access instanceof GlobalConstantAccess) {
            return [
                $this->buildGlobalConstantError($access->constantName),
            ];
        }

        if ($access instanceof DynamicConstantAccess) {
            // The name is not statically known; the lookup may still resolve to a
            // global constant, so report a conservative diagnostic.
            return [
                RuleErrorBuilder::message(
                    'Code is calling constant() with a dynamic constant name, which may read a global constant. Pass the value as an argument instead to make the dependency explicit.'
                )
                    ->identifier('constant.dynamic')
                    ->build(),
            ];
        }

        // Either not a builtin constant() call, or constant('Foo::BAR'), which
        // reads a class constant (PHP 8.3+) that ForbidUsingClassConstantsRule covers.
        return [];
    }

    private function buildGlobalConstantError(string $constantName): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Code is accessing global constant "%s". Pass it as an argument instead to make the dependency explicit.',
                $constantName
            )
        )
            ->identifier('constant.global')
            ->build();
    }
}