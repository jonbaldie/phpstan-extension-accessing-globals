<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
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
 * @implements Rule<CollectedDataNode>
 */
class NeverModifyGloballyDeclaredVariablesRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     * @return array<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var array<string, list<list<array<string, mixed>>>> $data */
        $data = $node->get(NeverModifyGloballyDeclaredVariablesCollector::class);
        $errors = [];

        foreach ($data as $file => $fileEntries) {
            $facts = [];
            foreach ($fileEntries as $entryList) {
                foreach ($entryList as $fact) {
                    $facts[] = $fact;
                }
            }

            $this->processFile($file, $facts, $errors);
        }

        return $errors;
    }

    /**
     * @param list<array<string, mixed>> $facts
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function processFile(string $file, array $facts, array &$errors): void
    {
        /**
         * @var array<int, array{
         *     id: int,
         *     start: int,
         *     end: int,
         *     type: 'function'|'method'|'closure'|'arrow',
         *     params: list<string>,
         *     uses: list<array{name: string, byRef: bool}>,
         *     isClosureOrArrow: bool,
         *     parent: int|null,
         *     declaredGlobals: list<string|null>
         * }> $tree
         */
        $tree = [];
        $nonTreeFacts = [];
        foreach ($facts as $fact) {
            if ($fact['kind'] === 'tree') {
                /** @var list<array{id: int, start: int, end: int, type: 'function'|'method'|'closure'|'arrow', params: list<string>, uses: list<array{name: string, byRef: bool}>, isClosureOrArrow: bool, parent: int|null}> $factTree */
                $factTree = $fact['tree'];
                foreach ($factTree as $fInfo) {
                    $tree[$fInfo['id']] = [
                        'id' => $fInfo['id'],
                        'start' => $fInfo['start'],
                        'end' => $fInfo['end'],
                        'type' => $fInfo['type'],
                        'params' => $fInfo['params'],
                        'uses' => $fInfo['uses'],
                        'isClosureOrArrow' => $fInfo['isClosureOrArrow'],
                        'parent' => $fInfo['parent'],
                        'declaredGlobals' => [],
                    ];
                }
            } else {
                $nonTreeFacts[] = $fact;
            }
        }

        if (empty($tree)) {
            return;
        }

        $findInnermost = static function (int $pos) use ($tree): ?int {
            $found = null;
            $smallestRange = null;
            foreach ($tree as $id => $info) {
                if ($info['start'] <= $pos && $pos <= $info['end']) {
                    $range = $info['end'] - $info['start'];
                    if ($smallestRange === null || $range < $smallestRange) {
                        $smallestRange = $range;
                        $found = $id;
                    }
                }
            }
            return $found;
        };

        foreach ($nonTreeFacts as $fact) {
            if ($fact['kind'] === 'global') {
                $pos = (int) $fact['pos'];
                $funcId = $findInnermost($pos);
                if ($funcId !== null && isset($tree[$funcId])) {
                    /** @var list<string|null> $names */
                    $names = $fact['names'];
                    foreach ($names as $name) {
                        $tree[$funcId]['declaredGlobals'][] = $name;
                    }
                }
            }
        }

        $hasGlobals = function (int $funcId) use ($tree, &$hasGlobals): bool {
            if (!empty($tree[$funcId]['declaredGlobals'])) {
                return true;
            }
            foreach ($tree as $candidate) {
                if ($candidate['parent'] === $funcId && $hasGlobals($candidate['id'])) {
                    return true;
                }
            }
            return false;
        };

        foreach ($tree as $rootId => $rootInfo) {
            if ($rootInfo['parent'] !== null) {
                continue;
            }

            if (!$hasGlobals($rootId)) {
                continue;
            }

            $rootFacts = [];
            foreach ($nonTreeFacts as $fact) {
                $pos = (int) $fact['pos'];
                if ($pos >= $rootInfo['start'] && $pos <= $rootInfo['end']) {
                    $rootFacts[] = $fact;
                }
            }

            usort($rootFacts, static fn(array $a, array $b): int => ((int) $a['pos']) <=> ((int) $b['pos']));

            $this->evaluateFunctionTree($file, $rootId, $rootFacts, $tree, $findInnermost, $errors);
        }
    }

    /**
     * @param list<array<string, mixed>> $rootFacts
     * @param array<int, array{
     *     id: int,
     *     start: int,
     *     end: int,
     *     type: 'function'|'method'|'closure'|'arrow',
     *     params: list<string>,
     *     uses: list<array{name: string, byRef: bool}>,
     *     isClosureOrArrow: bool,
     *     parent: int|null,
     *     declaredGlobals: list<string|null>
     * }> $tree
     * @param \Closure(int): (?int) $findInnermost
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function evaluateFunctionTree(
        string $file,
        int $rootId,
        array $rootFacts,
        array $tree,
        \Closure $findInnermost,
        array &$errors,
    ): void {
        /**
         * @var list<array{
         *     funcId: int,
         *     aliased: list<string|null>,
         *     byValue: list<string|null>,
         *     aliases: array<string, array<int|string, string|null>>
         * }> $bindingStack
         */
        $bindingStack = [
            [
                'funcId' => $rootId,
                'aliased' => $tree[$rootId]['declaredGlobals'],
                'byValue' => [],
                'aliases' => [],
            ],
        ];

        foreach ($rootFacts as $fact) {
            $pos = (int) $fact['pos'];
            $innermostId = $findInnermost($pos);
            if ($innermostId === null) {
                continue;
            }

            $chain = [];
            $curr = $innermostId;
            while ($curr !== null) {
                $chain[] = $curr;
                $curr = $tree[$curr]['parent'];
            }
            $chain = array_reverse($chain);

            if ($chain[0] !== $rootId) {
                continue;
            }

            while (count($bindingStack) > 1) {
                $topKey = array_key_last($bindingStack);
                $topFuncId = $bindingStack[$topKey]['funcId'];
                if (!in_array($topFuncId, $chain, true) || $pos > $tree[$topFuncId]['end']) {
                    array_pop($bindingStack);
                } else {
                    break;
                }
            }

            $onStackIds = array_column($bindingStack, 'funcId');
            foreach ($chain as $funcIdInChain) {
                if (!in_array($funcIdInChain, $onStackIds, true)) {
                    $parentKey = array_key_last($bindingStack);
                    if ($parentKey === null) {
                        break;
                    }
                    $parentFrame = $bindingStack[$parentKey];
                    $newFrame = $this->bindingsForNested($tree[$funcIdInChain], $parentFrame);
                    if (!empty($tree[$funcIdInChain]['declaredGlobals'])) {
                        $newFrame['aliased'] = array_merge(
                            $newFrame['aliased'],
                            $tree[$funcIdInChain]['declaredGlobals'],
                        );
                    }
                    $newFrame['funcId'] = $funcIdInChain;
                    $bindingStack[] = $newFrame;
                }
            }

            $frameKey = array_key_last($bindingStack);
            if ($frameKey === null) {
                continue;
            }

            if ($fact['kind'] === 'alias_assign') {
                /** @var string $lhs */
                $lhs = $fact['lhs'];
                unset($bindingStack[$frameKey]['aliases'][$lhs]);
                /** @var array<int|string, string|null> $slots */
                $slots = $fact['slots'];
                foreach ($slots as $slotKey => $referenced) {
                    if (in_array($referenced, $bindingStack[$frameKey]['aliased'], true)) {
                        $bindingStack[$frameKey]['aliases'][$lhs][$slotKey] = $referenced;
                    }
                }
            } elseif ($fact['kind'] === 'mutation') {
                /** @var string|null $targetName */
                $targetName = $fact['targetName'];
                $bindings = $bindingStack[$frameKey];

                if ($targetName !== null && array_key_exists($targetName, $bindings['aliases'])) {
                    $slots = $bindings['aliases'][$targetName];
                    $reached = false;
                    /** @var list<int|string> $outermostSlotKeys */
                    $outermostSlotKeys = $fact['outermostSlotKeys'];
                    foreach ($outermostSlotKeys as $slotKey) {
                        if (array_key_exists($slotKey, $slots)) {
                            $this->reportMutation($slots[$slotKey], $file, (int) $fact['line'], $errors);
                            $reached = true;
                        }
                    }

                    if ($reached) {
                        continue;
                    }
                }

                $throughObjectHandle = (bool) $fact['throughObjectHandle'];
                $candidates = $throughObjectHandle
                    ? array_merge($bindings['aliased'], $bindings['byValue'])
                    : $bindings['aliased'];

                $matchesKnownBinding = $targetName !== null
                    && in_array($targetName, $candidates, true);
                $matchesDynamicBinding = $targetName === null
                    && in_array(null, $candidates, true);

                if ($matchesKnownBinding || $matchesDynamicBinding) {
                    $this->reportMutation($targetName, $file, (int) $fact['line'], $errors);
                }
            }
        }
    }

    /**
     * @param array{
     *     id: int,
     *     start: int,
     *     end: int,
     *     type: 'function'|'method'|'closure'|'arrow',
     *     params: list<string>,
     *     uses: list<array{name: string, byRef: bool}>,
     *     isClosureOrArrow: bool,
     *     parent: int|null,
     *     declaredGlobals: list<string|null>
     * } $funcInfo
     * @param array{
     *     funcId: int,
     *     aliased: list<string|null>,
     *     byValue: list<string|null>,
     *     aliases: array<string, array<int|string, string|null>>
     * } $enclosing
     * @return array{
     *     funcId: int,
     *     aliased: list<string|null>,
     *     byValue: list<string|null>,
     *     aliases: array<string, array<int|string, string|null>>
     * }
     */
    private function bindingsForNested(array $funcInfo, array $enclosing): array
    {
        $aliased = [];
        $byValue = [];
        $aliases = [];

        if ($funcInfo['type'] === 'arrow') {
            $byValue = array_merge($enclosing['aliased'], $enclosing['byValue']);
            $aliases = $enclosing['aliases'];
        } elseif ($funcInfo['type'] === 'closure') {
            foreach ($funcInfo['uses'] as $use) {
                $name = $use['name'];
                if ($use['byRef'] && in_array($name, $enclosing['aliased'], true)) {
                    $aliased[] = $name;
                } elseif (array_key_exists($name, $enclosing['aliases'])) {
                    $aliases[$name] = $enclosing['aliases'][$name];
                } elseif (
                    in_array($name, $enclosing['aliased'], true)
                    || in_array($name, $enclosing['byValue'], true)
                ) {
                    $byValue[] = $name;
                }
            }
        }

        foreach ($funcInfo['params'] as $paramName) {
            $aliased = array_values(array_diff($aliased, [$paramName]));
            $byValue = array_values(array_diff($byValue, [$paramName]));
            unset($aliases[$paramName]);
        }

        return [
            'funcId' => $funcInfo['id'],
            'aliased' => $aliased,
            'byValue' => $byValue,
            'aliases' => $aliases,
        ];
    }

    /**
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function reportMutation(?string $targetName, string $file, int $line, array &$errors): void
    {
        $message = $targetName === null
            ? 'Code is modifying a variable with a dynamic name that was declared with the "global" keyword. Use dependency injection instead.'
            : sprintf(
                'Code is modifying variable $%s that was declared with the "global" keyword. Use dependency injection instead.',
                $targetName,
            );

        $errors[] = RuleErrorBuilder::message($message)
            ->file($file)
            ->line($line)
            ->identifier('modify.global')
            ->build();
    }
}
