<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects modifications to variables declared with the global keyword.
 *
 * @phpstan-import-type Fact from GlobalBindingFact
 * @phpstan-import-type Event from GlobalBindingFact
 * @phpstan-import-type FunctionScope from GlobalBindingFact
 * @phpstan-type Frame array{funcId: string, aliased: list<string|null>, byValue: list<string|null>, aliases: array<string, array<int|string, string|null>>}
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
        /** @var array<string, list<list<Fact>>> $data */
        $data = $node->get(NeverModifyGloballyDeclaredVariablesCollector::class);
        $errors = [];
        foreach ($data as $fileEntries) {
            $facts = [];
            foreach ($fileEntries as $entryList) {
                foreach ($entryList as $fact) {
                    $facts[] = $fact;
                }
            }
            $this->processFacts($facts, $errors);
        }
        return $errors;
    }

    /**
     * @param list<Fact> $facts
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function processFacts(array $facts, array &$errors): void
    {
        $tree = [];
        $events = [];
        $globals = [];
        foreach ($facts as $fact) {
            if ($fact['kind'] === 'scope') {
                $tree[$fact['scope']['id']] = $fact['scope'];
            } else {
                $events[] = $fact;
                if ($fact['kind'] === 'global') {
                    $globals[$fact['scopeId']] = array_merge($globals[$fact['scopeId']] ?? [], $fact['names']);
                }
            }
        }
        $rootFacts = [];
        foreach ($events as $fact) {
            $root = $fact['scopeId'];
            while ($tree[$root]['parent'] !== null) {
                $root = $tree[$root]['parent'];
            }
            $rootFacts[$root][] = $fact;
        }
        foreach ($rootFacts as $root => $events) {
            // A root and its lexical descendants share a source. Position is
            // only an ordering key, never a join or a containment test.
            usort($events, static fn(array $a, array $b): int => $a['pos'] <=> $b['pos']);
            $this->evaluateFunctionTree($root, $events, $tree, $globals, $errors);
        }
    }

    /**
     * @param list<Event> $rootFacts
     * @param array<string, FunctionScope> $tree
     * @param array<string, list<string|null>> $globals
     * @param array<\PHPStan\Rules\RuleError> $errors
     */
    private function evaluateFunctionTree(
        string $rootId,
        array $rootFacts,
        array $tree,
        array $globals,
        array &$errors,
    ): void {
        /** @var non-empty-list<Frame> $bindingStack */
        $bindingStack = [[
            'funcId' => $rootId,
            'aliased' => $globals[$rootId] ?? [],
            'byValue' => [],
            'aliases' => [],
        ]];

        foreach ($rootFacts as $fact) {
            $chain = [];
            $curr = $fact['scopeId'];
            while ($curr !== null) {
                $chain[] = $curr;
                $curr = $tree[$curr]['parent'];
            }
            $chain = array_reverse($chain);
            while (count($bindingStack) > 1) {
                $topKey = array_key_last($bindingStack);
                if (!in_array($bindingStack[$topKey]['funcId'], $chain, true)) {
                    array_pop($bindingStack);
                } else {
                    break;
                }
            }
            $onStackIds = array_column($bindingStack, 'funcId');
            foreach ($chain as $funcIdInChain) {
                if (!in_array($funcIdInChain, $onStackIds, true)) {
                    $parentFrame = $bindingStack[array_key_last($bindingStack)];
                    $newFrame = $this->bindingsForNested($tree[$funcIdInChain], $parentFrame);
                    $newFrame['aliased'] = array_merge($newFrame['aliased'], $globals[$funcIdInChain] ?? []);
                    $bindingStack[] = $newFrame;
                }
            }
            $frameKey = array_key_last($bindingStack);

            if ($fact['kind'] === 'alias_assign') {
                $lhs = $fact['lhs'];
                unset($bindingStack[$frameKey]['aliases'][$lhs]);
                foreach ($fact['slots'] as $slotKey => $referenced) {
                    if (in_array($referenced, $bindingStack[$frameKey]['aliased'], true)) {
                        $bindingStack[$frameKey]['aliases'][$lhs][$slotKey] = $referenced;
                    }
                }
            } elseif ($fact['kind'] === 'mutation') {
                $targetName = $fact['targetName'];
                $bindings = $bindingStack[$frameKey];
                if ($targetName !== null && array_key_exists($targetName, $bindings['aliases'])) {
                    $slots = $bindings['aliases'][$targetName];
                    $reached = false;
                    foreach ($fact['outermostSlotKeys'] as $slotKey) {
                        if (array_key_exists($slotKey, $slots)) {
                            $this->reportMutation($slots[$slotKey], $fact['file'], $fact['description'], $fact['line'], $errors);
                            $reached = true;
                        }
                    }
                    if ($reached) {
                        continue;
                    }
                }
                $candidates = $fact['throughObjectHandle']
                    ? array_merge($bindings['aliased'], $bindings['byValue'])
                    : $bindings['aliased'];
                $matchesKnownBinding = $targetName !== null && in_array($targetName, $candidates, true);
                $matchesDynamicBinding = $targetName === null && in_array(null, $candidates, true);
                if ($matchesKnownBinding || $matchesDynamicBinding) {
                    $this->reportMutation($targetName, $fact['file'], $fact['description'], $fact['line'], $errors);
                }
            }
        }
    }

    /**
     * @param FunctionScope $funcInfo
     * @param Frame $enclosing
     * @return Frame
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
                } elseif (in_array($name, $enclosing['aliased'], true) || in_array($name, $enclosing['byValue'], true)) {
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

    /** @param array<\PHPStan\Rules\RuleError> $errors */
    private function reportMutation(?string $targetName, string $file, string $description, int $line, array &$errors): void
    {
        $message = $targetName === null
            ? 'Code is modifying a variable with a dynamic name that was declared with the "global" keyword. Use dependency injection instead.'
            : sprintf(
                'Code is modifying variable $%s that was declared with the "global" keyword. Use dependency injection instead.',
                $targetName,
            );
        $errors[] = RuleErrorBuilder::message($message)
            ->file($file, $description)
            ->line($line)
            ->identifier('modify.global')
            ->build();
    }
}
