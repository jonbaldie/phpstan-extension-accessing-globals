<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue86;

interface StateMutator
{
    /**
     * @param array<string, mixed> $data
     */
    public function mutate(array &$data): void;
}

interface LoggingMutator
{
    /**
     * @param array<string, mixed> $data
     */
    public function mutate(array &$data): void;
}

class SingleMutator implements StateMutator, LoggingMutator
{
    public function mutate(array &$data): void
    {
        $data['mutated'] = true;
    }
}

function updateWithSingleType(SingleMutator $mutator): void
{
    global $appState;
    $mutator->mutate($appState);
}

function updateWithUnionType(StateMutator|LoggingMutator $mutator): void
{
    global $appState;
    $mutator->mutate($appState);
}

function updateWithNullableUnion(StateMutator|null $mutator): void
{
    global $appState;
    $mutator?->mutate($appState);
}

function updateWithIntersectionType(StateMutator&LoggingMutator $mutator): void
{
    global $appState;
    $mutator->mutate($appState);
}

function updateWithDnfType((StateMutator&LoggingMutator)|null $mutator): void
{
    global $appState;
    $mutator?->mutate($appState);
}
