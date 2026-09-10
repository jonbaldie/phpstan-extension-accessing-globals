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

        // Collect all globally declared variable names
        $globalVars = [];
        $this->collectGlobalDeclarations($node, $globalVars);

        if (empty($globalVars)) {
            return [];
        }

        // Find all assignments to those variables
        $this->findAssignmentsToGlobals($node, $globalVars, $errors);

        return $errors;
    }

    /**
     * Traverse the function to collect all global variable declarations.
     *
     * @param Node\FunctionLike $function
     * @param array<string> $globalVars
     */
    private function collectGlobalDeclarations(Node\FunctionLike $function, array &$globalVars): void
    {
        $traverser = new NodeTraverser();
        $visitor = new class($globalVars) extends NodeVisitorAbstract {
            /** @var array<string> */
            private array $globalVars;

            /** @param array<string> $globalVars */
            public function __construct(array &$globalVars)
            {
                $this->globalVars = &$globalVars;
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
                        if ($var instanceof Node\Expr\Variable && is_string($var->name)) {
                            $this->globalVars[] = $var->name;
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
     * @param array<string> $globalVars
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function findAssignmentsToGlobals(
        Node\FunctionLike $function,
        array $globalVars,
        array &$errors
    ): void {
        $traverser = new NodeTraverser();
        $visitor = new class($globalVars, $errors) extends NodeVisitorAbstract {
            /** @var list<array<string>> */
            private array $bindingStack;

            /** @var array<\PHPStan\Rules\RuleError> */
            private array $errors;

            /**
             * @param array<string> $globalVars
             * @param array<\PHPStan\Rules\RuleError> $errors
             */
            public function __construct(array $globalVars, array &$errors)
            {
                $this->bindingStack = [$globalVars];
                $this->errors = &$errors;
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
                foreach (MutationTargetResolver::resolve($node) as $target) {
                    // `unset($db)` removes the local binding created by
                    // `global $db`; it does not remove the global variable.
                    if (
                        $node instanceof Node\Stmt\Unset_
                        && $target instanceof Node\Expr\Variable
                    ) {
                        continue;
                    }

                    $globalTarget = $target;
                    while ($globalTarget instanceof Node\Expr\ArrayDimFetch) {
                        $globalTarget = $globalTarget->var;
                    }

                    if (
                        $globalTarget instanceof Node\Expr\Variable &&
                        is_string($globalTarget->name) &&
                        in_array($globalTarget->name, $this->currentGlobals(), true)
                    ) {
                        $this->errors[] = RuleErrorBuilder::message(
                            sprintf(
                                'Code is modifying variable $%s that was declared with the "global" keyword. Use dependency injection instead.',
                                $globalTarget->name,
                            ),
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
             * @return array<string>
             */
            private function currentGlobals(): array
            {
                return $this->bindingStack[array_key_last($this->bindingStack)] ?? [];
            }

            /**
             * @return array<string>
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
