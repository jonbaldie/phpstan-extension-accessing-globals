<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\NodeTraverser;
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
     * Traverse the function to find assignments to globally declared variables.
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
            /** @var array<string> */
            private array $globalVars;

            /** @var array<\PHPStan\Rules\RuleError> */
            private array $errors;

            /**
             * @param array<string> $globalVars
             * @param array<\PHPStan\Rules\RuleError> $errors
             */
            public function __construct(array $globalVars, array &$errors)
            {
                $this->globalVars = $globalVars;
                $this->errors = &$errors;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\Expr\Assign) {
                    $assignedTo = $node->var;

                    if (
                        $assignedTo instanceof Node\Expr\Variable &&
                        is_string($assignedTo->name) &&
                        in_array($assignedTo->name, $this->globalVars, true)
                    ) {
                        $this->errors[] = RuleErrorBuilder::message(
                            sprintf(
                                'Code is modifying global variable $%s. Use dependency injection instead.',
                                $assignedTo->name,
                            ),
                        )
                            ->line($node->getLine())
                            ->identifier("modify.global")
                            ->build();
                    }
                }
                return null;
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($function->getStmts() ?? []);
    }
}
