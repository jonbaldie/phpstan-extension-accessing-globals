<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects modifications to variables that were declared with the global keyword.
 *
 * Example violations:
 * function test() {
 *     global $db;         // Declaration (caught by NeverAccessGlobalsRule)
 *     $db = new PDO(...); // VIOLATION - modifying global variable (caught by this rule)
 * }
 *
 * @implements Rule<Node\FunctionLike>
 */
class NeverModifyGloballyDeclaredVariablesRule implements Rule
{
    public function __construct(
        private readonly MutationTargetResolver $mutationTargetResolver,
    ) {
    }

    public function getNodeType(): string
    {
        return Node\FunctionLike::class;
    }

    /**
     * @param Node\FunctionLike $node
     * @return array<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        // Collect all globally declared variable bindings.
        $globalVars = [];
        $this->collectGlobalDeclarations($node, $scope, $globalVars);

        if (empty($globalVars)) {
            return [];
        }

        // Find all assignments to those bindings.
        $this->findAssignmentsToGlobals($node, $scope, $globalVars, $errors, $this->mutationTargetResolver);

        return $errors;
    }

    /**
     * Traverse the function to collect all global variable declarations.
     *
     * @param Node\FunctionLike $function
     * @param array<string|null> $globalVars
     */
    private function collectGlobalDeclarations(
        Node\FunctionLike $function,
        Scope $scope,
        array &$globalVars,
    ): void {
        $traverser = new NodeTraverser();
        $visitor = new class($globalVars, $scope) extends NodeVisitorAbstract {
            /** @var array<string|null> */
            private array $globalVars;

            private Scope $scope;

            /** @param array<string|null> $globalVars */
            public function __construct(array &$globalVars, Scope $scope)
            {
                $this->globalVars = &$globalVars;
                $this->scope = $scope;
            }

            public function enterNode(Node $node)
            {
                // Nested function-likes are processed by their own invocation of
                // this rule; `global` declarations do not leak into nested scopes.
                if ($node instanceof Node\FunctionLike) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }
                if ($node instanceof Node\Stmt\Global_) {
                    foreach ($node->vars as $var) {
                        if ($var instanceof Node\Expr\Variable) {
                            $this->globalVars[] = GlobalVariableNameResolver::resolve($var, $this->scope);
                        }
                    }
                }
                return null;
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($function->getStmts() ?? []);
    }

    /**
     * Traverse the function to find mutations of globally declared variables.
     *
     * @param Node\FunctionLike $function
     * @param array<string|null> $globalVars
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function findAssignmentsToGlobals(
        Node\FunctionLike $function,
        Scope $scope,
        array $globalVars,
        array &$errors,
        MutationTargetResolver $mutationTargetResolver,
    ): void {
        if ($scope instanceof \PHPStan\Analyser\MutatingScope) {
            foreach ($function->getParams() as $param) {
                if (
                    $param->var instanceof Node\Expr\Variable
                    && is_string($param->var->name)
                ) {
                    $paramType = null;
                    if ($param->type instanceof Node\Name) {
                        $paramType = $scope->resolveTypeByName($param->type);
                    } elseif ($param->type instanceof Node\NullableType && $param->type->type instanceof Node\Name) {
                        $paramType = new \PHPStan\Type\NullableType($scope->resolveTypeByName($param->type->type));
                    }

                    if ($paramType !== null) {
                        $scope = $scope->assignVariable(
                            $param->var->name,
                            $paramType,
                            $paramType,
                            \PHPStan\TrinaryLogic::createYes(),
                        );
                    }
                }
            }
        }

        $traverser = new NodeTraverser();
        $visitor = new class($globalVars, $scope, $errors, $mutationTargetResolver) extends NodeVisitorAbstract {
            /** @var list<array<string|null>> */
            private array $bindingStack;

            private Scope $scope;

            /** @var array<\PHPStan\Rules\RuleError> */
            private array $errors;

            private MutationTargetResolver $mutationTargetResolver;

            /**
              * @param array<string|null> $globalVars
              * @param array<\PHPStan\Rules\RuleError> $errors
              */
            public function __construct(
                array $globalVars,
                Scope $scope,
                array &$errors,
                MutationTargetResolver $mutationTargetResolver,
            ) {
                $this->bindingStack = [$globalVars];
                $this->scope = $scope;
                $this->errors = &$errors;
                $this->mutationTargetResolver = $mutationTargetResolver;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\FunctionLike) {
                    $this->bindingStack[] = $this->bindingsForNested($node);
                    if ($this->currentGlobals() === []) {
                        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                    }

                    return null;
                }

                if ($node instanceof Node\Expr\Assign) {
                    if (
                        $this->scope instanceof \PHPStan\Analyser\MutatingScope
                        && $node->var instanceof Node\Expr\Variable
                        && is_string($node->var->name)
                    ) {
                        $assignedType = $this->scope->getType($node->expr);
                        $this->scope = $this->scope->assignVariable(
                            $node->var->name,
                            $assignedType,
                            $assignedType,
                            \PHPStan\TrinaryLogic::createYes(),
                        );
                    }
                }

                foreach ($this->mutationTargetResolver->resolve($node, $this->scope) as $target) {
                    // `unset($db)` removes the local binding created by
                    // `global $db`; it does not remove the global variable.
                    if (
                        $node instanceof Node\Stmt\Unset_
                        && $target instanceof Node\Expr\Variable
                    ) {
                        continue;
                    }

                    $globalTarget = $target;
                    while (
                        $globalTarget instanceof Node\Expr\ArrayDimFetch
                        || $globalTarget instanceof Node\Expr\PropertyFetch
                    ) {
                        $globalTarget = $globalTarget->var;
                    }

                    if (!$globalTarget instanceof Node\Expr\Variable) {
                        continue;
                    }

                    $targetName = GlobalVariableNameResolver::resolve($globalTarget, $this->scope);
                    // A null binding represents an unresolved dynamic name. Match
                    // only another unresolved target and keep its diagnostic generic.
                    $matchesKnownBinding = $targetName !== null
                        && in_array($targetName, $this->currentGlobals(), true);
                    $matchesDynamicBinding = $targetName === null
                        && in_array(null, $this->currentGlobals(), true);

                    if ($matchesKnownBinding || $matchesDynamicBinding) {
                        $message = $targetName === null
                            ? 'Code is modifying a variable with a dynamic name that was declared with the "global" keyword. Use dependency injection instead.'
                            : sprintf(
                                'Code is modifying variable $%s that was declared with the "global" keyword. Use dependency injection instead.',
                                $targetName,
                            );

                        $this->errors[] = RuleErrorBuilder::message(
                            $message,
                        )
                            ->line($node->getLine())
                            ->identifier("modify.global")
                            ->build();
                    }
                }
                return null;
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\FunctionLike) {
                    array_pop($this->bindingStack);
                }

                return null;
            }

            /**
             * @return array<string|null>
             */
            private function currentGlobals(): array
            {
                return $this->bindingStack[array_key_last($this->bindingStack)] ?? [];
            }

            /**
             * @return array<string|null>
             */
            private function bindingsForNested(Node\FunctionLike $node): array
            {
                $inherited = [];
                if ($node instanceof Node\Expr\Closure) {
                    foreach ($node->uses as $use) {
                        $name = $use->var->name;
                        if (
                            $use->byRef
                            && is_string($name)
                            && in_array($name, $this->currentGlobals(), true)
                        ) {
                            $inherited[] = $name;
                        }
                    }
                }
                foreach ($node->getParams() as $param) {
                    if (
                        $param->var instanceof Node\Expr\Variable
                        && is_string($param->var->name)
                    ) {
                        $inherited = array_values(array_diff($inherited, [$param->var->name]));
                    }
                }

                return $inherited;
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($function->getStmts() ?? []);
    }
}
