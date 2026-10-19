<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\FileNode;

/**
 * Collects facts about function-like declarations, global variable declarations,
 * array-literal alias bindings, and mutation targets under PHPStan's real,
 * flow-sensitive scope at each node.
 *
 * @implements Collector<Node, list<array<string, mixed>>>
 */
final class NeverModifyGloballyDeclaredVariablesCollector implements Collector
{
    public function __construct(
        private readonly MutationTargetResolver $mutationTargetResolver,
    ) {
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param Node $node
     * @return list<array<string, mixed>>|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $facts = [];

        if (get_class($node) === FileNode::class) {
            $finder = new NodeFinder();
            /** @var list<Node\FunctionLike> $functions */
            $functions = $finder->findInstanceOf($node->getNodes(), Node\FunctionLike::class);
            $tree = [];
            foreach ($functions as $index => $f) {
                $params = [];
                foreach ($f->getParams() as $param) {
                    if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                        $params[] = $param->var->name;
                    }
                }

                $uses = [];
                if ($f instanceof Node\Expr\Closure) {
                    foreach ($f->uses as $use) {
                        if (is_string($use->var->name)) {
                            $uses[] = [
                                'name' => $use->var->name,
                                'byRef' => $use->byRef,
                            ];
                        }
                    }
                }

                $isClosureOrArrow = $f instanceof Node\Expr\Closure || $f instanceof Node\Expr\ArrowFunction;

                $tree[] = [
                    'id' => $index,
                    'start' => $f->getStartFilePos(),
                    'end' => $f->getEndFilePos(),
                    'type' => $f instanceof Node\Expr\ArrowFunction
                        ? 'arrow'
                        : ($f instanceof Node\Expr\Closure
                            ? 'closure'
                            : ($f instanceof Node\Stmt\ClassMethod ? 'method' : 'function')),
                    'params' => $params,
                    'uses' => $uses,
                    'isClosureOrArrow' => $isClosureOrArrow,
                ];
            }

            // Only closures and arrow functions can inherit lexical bindings from an enclosing function.
            foreach ($tree as $i => &$info) {
                $parent = null;
                if ($info['isClosureOrArrow']) {
                    $smallestRange = null;
                    foreach ($tree as $j => $candidate) {
                        if ($i === $j) {
                            continue;
                        }
                        if ($candidate['start'] <= $info['start'] && $info['end'] <= $candidate['end']) {
                            $range = $candidate['end'] - $candidate['start'];
                            if ($smallestRange === null || $range < $smallestRange) {
                                $smallestRange = $range;
                                $parent = $candidate['id'];
                            }
                        }
                    }
                }
                $info['parent'] = $parent;
            }
            unset($info);

            $facts[] = [
                'kind' => 'tree',
                'tree' => $tree,
            ];
        }

        if ($node instanceof Node\Stmt\Global_) {
            $names = [];
            foreach ($node->vars as $var) {
                if ($var instanceof Node\Expr\Variable) {
                    $names[] = GlobalVariableNameResolver::resolve($var, $scope);
                }
            }

            $facts[] = [
                'kind' => 'global',
                'pos' => $node->getStartFilePos(),
                'line' => $node->getLine(),
                'names' => $names,
            ];
        }

        if ($node instanceof Node\Expr\Assign) {
            $lhs = $node->var;
            if ($lhs instanceof Node\Expr\Variable && is_string($lhs->name)) {
                $slots = [];
                if ($node->expr instanceof Node\Expr\Array_ || $node->expr instanceof Node\Expr\List_) {
                    $implicitIndex = 0;
                    foreach ($node->expr->items as $item) {
                        if ($item === null) {
                            continue;
                        }

                        if ($item->key === null) {
                            $slotKey = $implicitIndex;
                            $implicitIndex = $implicitIndex + 1;
                        } else {
                            $slotKey = self::constantScalarKey($item->key, $scope);
                            if ($slotKey === null) {
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

                        $referencedName = GlobalVariableNameResolver::resolve($referenced, $scope);
                        if ($referencedName !== null) {
                            $slots[$slotKey] = $referencedName;
                        }
                    }
                }

                $facts[] = [
                    'kind' => 'alias_assign',
                    'pos' => $node->getStartFilePos(),
                    'line' => $node->getLine(),
                    'lhs' => $lhs->name,
                    'slots' => $slots,
                ];
            }
        }

        foreach ($this->mutationTargetResolver->resolve($node, $scope) as $target) {
            if ($node instanceof Node\Stmt\Unset_ && $target instanceof Node\Expr\Variable) {
                continue;
            }

            $globalTarget = $target;
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

            $targetName = GlobalVariableNameResolver::resolve($globalTarget, $scope);

            $outermostSlotKeys = [];
            if ($outermostFetch instanceof Node\Expr\ArrayDimFetch && $outermostFetch->dim !== null) {
                $outermostSlotKeys = self::constantSlotKeys($outermostFetch->dim, $scope);
            }

            $facts[] = [
                'kind' => 'mutation',
                'pos' => $node->getStartFilePos(),
                'line' => $node->getLine(),
                'targetName' => $targetName,
                'throughObjectHandle' => $throughObjectHandle,
                'outermostSlotKeys' => $outermostSlotKeys,
            ];
        }

        return !empty($facts) ? $facts : null;
    }

    /**
     * @return int|string|null
     */
    private static function constantScalarKey(Node\Expr $key, Scope $scope)
    {
        $values = $scope->getType($key)->getConstantScalarValues();
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
    private static function constantSlotKeys(Node\Expr $dim, Scope $scope): array
    {
        $keys = [];
        foreach ($scope->getType($dim)->getConstantScalarValues() as $value) {
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
}
