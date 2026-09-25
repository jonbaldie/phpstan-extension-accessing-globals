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

                if (
                    $this->scope instanceof \PHPStan\Analyser\MutatingScope
                    && ($node instanceof Node\Expr\Assign || $node instanceof Node\Expr\AssignOp)
                    && $node->var instanceof Node\Expr\Variable
                    && is_string($node->var->name)
                ) {
                    $assignedType = $node instanceof Node\Expr\AssignOp
                        ? $this->scope->getType($node)
                        : $this->scope->getType($node->expr);
                    $this->scope = $this->scope->assignVariable(
                        $node->var->name,
                        $assignedType,
                        $assignedType,
                        \PHPStan\TrinaryLogic::createYes(),
                    );
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
                    if ($param->type !== null) {
                        $isNullable = $param->default instanceof Node\Expr\ConstFetch
                            && strtolower((string) $param->default->name) === 'null';
                        $paramType = $scope->getFunctionType($param->type, $isNullable, $param->variadic);
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
            /**
             * One frame per function-like scope. `aliased` bindings still refer
             * to the global variable itself, so any write to them mutates it.
             * `byValue` bindings are copies of the binding (an arrow function's
             * implicit capture, or a by-value `use`); only writes reached
             * through an object handle escape the copy.
             *
             * `aliases` records bindings created by by-reference items of an
             * array literal (`$alias = [&$db];`): the alias variable holds the
             * global's reference in a slot, so dimension writes through a
             * registered slot mutate the global itself.
             *
             * @var list<array{aliased: array<string|null>, byValue: array<string|null>, aliases: array<string, array<int|string, string|null>>}>
             */
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
                $this->bindingStack = [['aliased' => $globalVars, 'byValue' => [], 'aliases' => []]];
                $this->scope = $scope;
                $this->errors = &$errors;
                $this->mutationTargetResolver = $mutationTargetResolver;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\FunctionLike) {
                    $this->bindingStack[] = $this->bindingsForNested($node);
                    if ($this->currentBindings() === ['aliased' => [], 'byValue' => [], 'aliases' => []]) {
                        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                    }

                    return null;
                }

                if ($node instanceof Node\Expr\Assign || $node instanceof Node\Expr\AssignOp) {
                    if (
                        $this->scope instanceof \PHPStan\Analyser\MutatingScope
                        && $node->var instanceof Node\Expr\Variable
                        && is_string($node->var->name)
                    ) {
                        $assignedType = $node instanceof Node\Expr\AssignOp
                            ? $this->scope->getType($node)
                            : $this->scope->getType($node->expr);
                        $this->scope = $this->scope->assignVariable(
                            $node->var->name,
                            $assignedType,
                            $assignedType,
                            \PHPStan\TrinaryLogic::createYes(),
                        );
                    }

                    if ($node instanceof Node\Expr\Assign) {
                        $this->trackAliases($node);
                    }
                }

                // PHPStan's own walk rewrites `$obj?->method()` into a
                // MethodCall before rules see it; this traversal sees the raw
                // node, so apply the same rewrite for by-reference arguments.
                $mutationNode = $node instanceof Node\Expr\NullsafeMethodCall
                    ? new Node\Expr\MethodCall($node->var, $node->name, $node->args, $node->getAttributes())
                    : $node;

                foreach ($this->mutationTargetResolver->resolve($mutationNode, $this->scope) as $target) {
                    // `unset($db)` removes the local binding created by
                    // `global $db`; it does not remove the global variable.
                    if (
                        $node instanceof Node\Stmt\Unset_
                        && $target instanceof Node\Expr\Variable
                    ) {
                        continue;
                    }

                    $globalTarget = $target;
                    // A write reached through a property fetch goes through an
                    // object handle, which a by-value capture shares with the
                    // global; array dimensions are copied, so they do not.
                    $throughDimensions = false;
                    $throughObjectHandle = false;
                    $outermostFetch = null;
                    while (
                        $globalTarget instanceof Node\Expr\ArrayDimFetch
                        || $globalTarget instanceof Node\Expr\PropertyFetch
                    ) {
                        if ($outermostFetch === null) {
                            $outermostFetch = $globalTarget;
                        }
                        $throughDimensions = true;
                        $throughObjectHandle = $throughObjectHandle
                            || $globalTarget instanceof Node\Expr\PropertyFetch;
                        $globalTarget = $globalTarget->var;
                    }

                    if (!$globalTarget instanceof Node\Expr\Variable) {
                        continue;
                    }

                    $bindings = $this->currentBindings();
                    $targetName = GlobalVariableNameResolver::resolve($globalTarget, $this->scope);

                    // Writes through an alias created by an array literal
                    // (`$alias = [&$db];`) mutate the referenced global, not
                    // the alias container: only when the write targets a
                    // registered slot of the alias. Unregistered slots, appends
                    // and property writes rebind the alias container itself.
                    if ($targetName !== null && array_key_exists($targetName, $bindings['aliases'])) {
                        $slots = $bindings['aliases'][$targetName];
                        $reached = false;
                        if (
                            $outermostFetch instanceof Node\Expr\ArrayDimFetch
                            && $outermostFetch->dim !== null
                            && $this->scope instanceof \PHPStan\Analyser\MutatingScope
                        ) {
                            foreach ($this->constantSlotKeys($outermostFetch->dim) as $slotKey) {
                                if (!array_key_exists($slotKey, $slots)) {
                                    continue;
                                }

                                $this->reportMutation($slots[$slotKey], $node);
                                $reached = true;
                            }
                        }

                        if ($reached) {
                            continue;
                        }
                    }

                    $candidates = $throughObjectHandle
                        ? array_merge($bindings['aliased'], $bindings['byValue'])
                        : $bindings['aliased'];

                    // A null binding represents an unresolved dynamic name. Match
                    // only another unresolved target and keep its diagnostic generic.
                    $matchesKnownBinding = $targetName !== null
                        && in_array($targetName, $candidates, true);
                    $matchesDynamicBinding = $targetName === null
                        && in_array(null, $candidates, true);

                    if ($matchesKnownBinding || $matchesDynamicBinding) {
                        $this->reportMutation($targetName, $node);
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
             * @return array{aliased: array<string|null>, byValue: array<string|null>, aliases: array<string, array<int|string, string|null>>}
             */
            private function currentBindings(): array
            {
                return $this->bindingStack[array_key_last($this->bindingStack)]
                    ?? ['aliased' => [], 'byValue' => [], 'aliases' => []];
            }

            /**
             * Record an alias binding created by a by-reference item of an
             * array literal (`$alias = [&$db];`). A whole-variable rebind of
             * the alias container discards any reference it carried.
             */
            private function trackAliases(Node\Expr\Assign $node): void
            {
                $frameKey = array_key_last($this->bindingStack);
                if ($frameKey === null) {
                    return;
                }

                $lhs = $node->var;
                if ($lhs instanceof Node\Expr\Variable && is_string($lhs->name)) {
                    unset($this->bindingStack[$frameKey]['aliases'][$lhs->name]);
                }

                if (
                    !$lhs instanceof Node\Expr\Variable
                    || !is_string($lhs->name)
                    || (!$node->expr instanceof Node\Expr\Array_ && !$node->expr instanceof Node\Expr\List_)
                    || !$this->scope instanceof \PHPStan\Analyser\MutatingScope
                ) {
                    return;
                }

                $implicitIndex = 0;
                foreach ($node->expr->items as $item) {
                    if ($item === null) {
                        continue;
                    }

                    if ($item->key === null) {
                        $slotKey = $implicitIndex;
                        $implicitIndex = $implicitIndex + 1;
                    } else {
                        $slotKey = $this->constantScalarKey($item->key);
                        if ($slotKey === null) {
                            // The slot cannot be resolved now or later; writes
                            // through it stay unreported.
                            continue;
                        }

                        if (is_int($slotKey)) {
                            $implicitIndex = max($implicitIndex, $slotKey + 1);
                        }
                    }

                    if (!$item->byRef) {
                        continue;
                    }

                    $referenced = $item->value;
                    while (
                        $referenced instanceof Node\Expr\ArrayDimFetch
                        || $referenced instanceof Node\Expr\PropertyFetch
                    ) {
                        $referenced = $referenced->var;
                    }

                    if (!$referenced instanceof Node\Expr\Variable) {
                        continue;
                    }

                    $referencedName = GlobalVariableNameResolver::resolve($referenced, $this->scope);
                    if (
                        $referencedName === null
                        || !in_array($referencedName, $this->bindingStack[$frameKey]['aliased'], true)
                    ) {
                        continue;
                    }

                    $this->bindingStack[$frameKey]['aliases'][$lhs->name][$slotKey] = $referencedName;
                }
            }

            /**
             * A literal, canonical array key (int, canonical numeric string
             * or other string); bool keys follow PHP's cast to int. Returns
             * null for dynamic or non-scalar keys.
             *
             * @return int|string|null
             */
            private function constantScalarKey(Node\Expr $key)
            {
                if (!$this->scope instanceof \PHPStan\Analyser\MutatingScope) {
                    return null;
                }

                $values = $this->scope->getType($key)->getConstantScalarValues();
                if (count($values) !== 1) {
                    return null;
                }

                $value = $values[0];
                if (is_int($value) || (is_string($value) && preg_match('/^(0|[1-9]\d*)$/', $value) === 1)) {
                    return (int) $value;
                }

                if (is_string($value)) {
                    return $value;
                }

                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }

                return null;
            }

            /**
             * @return list<int|string>
             */
            private function constantSlotKeys(Node\Expr $dim): array
            {
                if (!$this->scope instanceof \PHPStan\Analyser\MutatingScope) {
                    return [];
                }

                $keys = [];
                foreach ($this->scope->getType($dim)->getConstantScalarValues() as $value) {
                    if (is_int($value) || (is_string($value) && preg_match('/^(0|[1-9]\d*)$/', $value) === 1)) {
                        $keys[] = (int) $value;
                    } elseif (is_string($value)) {
                        $keys[] = $value;
                    } elseif (is_bool($value)) {
                        $keys[] = $value ? 1 : 0;
                    }
                }

                return $keys;
            }

            private function reportMutation(?string $targetName, Node $node): void
            {
                $message = $targetName === null
                    ? 'Code is modifying a variable with a dynamic name that was declared with the "global" keyword. Use dependency injection instead.'
                    : sprintf(
                        'Code is modifying variable $%s that was declared with the "global" keyword. Use dependency injection instead.',
                        $targetName,
                    );

                $this->errors[] = RuleErrorBuilder::message($message)
                    ->line($node->getLine())
                    ->identifier("modify.global")
                    ->build();
            }

            /**
             * @return array{aliased: array<string|null>, byValue: array<string|null>, aliases: array<string, array<int|string, string|null>>}
             */
            private function bindingsForNested(Node\FunctionLike $node): array
            {
                $enclosing = $this->currentBindings();
                $aliased = [];
                $byValue = [];
                $aliases = [];

                if ($node instanceof Node\Expr\ArrowFunction) {
                    // An arrow function implicitly captures every enclosing
                    // variable it uses, always by value. The copy still shares
                    // the reference slots with the global.
                    $byValue = array_merge($enclosing['aliased'], $enclosing['byValue']);
                    $aliases = $enclosing['aliases'];
                } elseif ($node instanceof Node\Expr\Closure) {
                    foreach ($node->uses as $use) {
                        $name = $use->var->name;
                        if (!is_string($name)) {
                            continue;
                        }

                        if ($use->byRef && in_array($name, $enclosing['aliased'], true)) {
                            // A by-ref capture of the global binding still
                            // aliases the global variable.
                            $aliased[] = $name;
                        } elseif (array_key_exists($name, $enclosing['aliases'])) {
                            // A by-ref or by-value capture of an alias
                            // container keeps the reference slots alive.
                            $aliases[$name] = $enclosing['aliases'][$name];
                        } elseif (
                            in_array($name, $enclosing['aliased'], true)
                            || in_array($name, $enclosing['byValue'], true)
                        ) {
                            // Everything else is a copy of the binding: a
                            // by-value capture, or a by-ref capture of a copy.
                            $byValue[] = $name;
                        }
                    }
                }

                foreach ($node->getParams() as $param) {
                    if (
                        $param->var instanceof Node\Expr\Variable
                        && is_string($param->var->name)
                    ) {
                        $aliased = array_values(array_diff($aliased, [$param->var->name]));
                        $byValue = array_values(array_diff($byValue, [$param->var->name]));
                        unset($aliases[$param->var->name]);
                    }
                }

                return ['aliased' => $aliased, 'byValue' => $byValue, 'aliases' => $aliases];
            }
        };

        $traverser->addVisitor($visitor);
        $traverser->traverse($function->getStmts() ?? []);
    }
}
