<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue69;

final class StateMutator
{
    /**
     * @param array<string, mixed> $data
     */
    public function mutate(array &$data): void
    {
        $data['mutated'] = true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function readOnly(array $data): void
    {
    }
}

function updateStateWithNullsafeCall(StateMutator $mutator): void
{
    global $appState;
    $mutator?->mutate($appState);
}

function updateStateWithNullableReceiver(?StateMutator $mutator): void
{
    global $appState;
    $mutator?->mutate(data: $appState['nested']);
}

function readStateWithNullsafeCall(?StateMutator $mutator): void
{
    global $appState;

    // By-value call must not be reported
    $mutator?->readOnly($appState);
}
