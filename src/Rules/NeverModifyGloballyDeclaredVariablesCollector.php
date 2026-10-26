<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects declarations, aliases and mutations under PHPStan's real,
 * flow-sensitive scope. No reflection or AST objects cross this boundary.
 *
 * @phpstan-import-type Fact from GlobalBindingFact
 * @implements Collector<Node, list<Fact>>
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

    /** @return list<Fact>|null */
    public function processNode(Node $node, Scope $scope): ?array
    {
        GlobalBindingScope::annotate($node);
        $facts = [];

        if ($node instanceof Node\Stmt\Global_) {
            $names = [];
            foreach ($node->vars as $var) {
                if ($var instanceof Node\Expr\Variable) {
                    $names[] = GlobalVariableNameResolver::resolve($var, $scope);
                }
            }
            $facts[] = ['kind' => 'global', 'names' => $names];
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
                            $slotKey = $implicitIndex++;
                        } else {
                            $values = $scope->getType($item->key)->getConstantScalarValues();
                            $slotKey = count($values) === 1 ? self::normalizeKey($values[0]) : null;
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
                        while ($referenced instanceof Node\Expr\ArrayDimFetch || $referenced instanceof Node\Expr\PropertyFetch) {
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
                $facts[] = ['kind' => 'alias_assign', 'lhs' => $lhs->name, 'slots' => $slots];
            }
        }

        foreach ($this->mutationTargetResolver->resolve($node, $scope) as $target) {
            if ($node instanceof Node\Stmt\Unset_ && $target instanceof Node\Expr\Variable) {
                continue;
            }
            $globalTarget = $target;
            $throughObjectHandle = false;
            $outermostFetch = null;
            while ($globalTarget instanceof Node\Expr\ArrayDimFetch || $globalTarget instanceof Node\Expr\PropertyFetch) {
                $outermostFetch ??= $globalTarget;
                $throughObjectHandle = $throughObjectHandle || $globalTarget instanceof Node\Expr\PropertyFetch;
                $globalTarget = $globalTarget->var;
            }
            if (!$globalTarget instanceof Node\Expr\Variable) {
                continue;
            }
            $outermostSlotKeys = [];
            if ($outermostFetch instanceof Node\Expr\ArrayDimFetch && $outermostFetch->dim !== null) {
                $outermostSlotKeys = self::constantSlotKeys($outermostFetch->dim, $scope);
            }
            $facts[] = [
                'kind' => 'mutation',
                'targetName' => GlobalVariableNameResolver::resolve($globalTarget, $scope),
                'throughObjectHandle' => $throughObjectHandle,
                'outermostSlotKeys' => $outermostSlotKeys,
            ];
        }

        if ($facts === []) {
            return null;
        }
        $scopes = GlobalBindingScope::describe($node, $scope);
        if ($scopes === []) {
            return null;
        }
        $location = [
            'scopeId' => $scopes[array_key_last($scopes)]['id'],
            'pos' => $node->getStartFilePos(),
            'line' => $node->getLine(),
            'file' => $scope->getTraitReflection()?->getFileName() ?? $scope->getFile(),
            'description' => $scope->getFileDescription(),
        ];
        $result = [];
        foreach ($scopes as $functionScope) {
            $result[] = ['kind' => 'scope', 'scope' => $functionScope];
        }
        foreach ($facts as $fact) {
            $result[] = $fact + $location;
        }
        return $result;
    }

    /** @return list<int|string> */
    private static function constantSlotKeys(Node\Expr $dim, Scope $scope): array
    {
        $keys = [];
        foreach ($scope->getType($dim)->getConstantScalarValues() as $value) {
            $key = self::normalizeKey($value);
            if ($key !== null) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    private static function normalizeKey(int|float|string|bool|null $value): int|string|null
    {
        if (is_int($value) || (is_string($value) && preg_match('/^(0|[1-9]\d*)$/', $value) === 1)) {
            return (int) $value;
        }
        if (is_string($value)) {
            return $value;
        }
        return is_bool($value) ? (int) $value : null;
    }
}
