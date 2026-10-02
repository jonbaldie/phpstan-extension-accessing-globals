<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue100;

interface StateMutator
{
    /**
     * @param array<string, mixed> $data
     */
    public function mutate(array &$data): void;
}

function updateWithNativeType(StateMutator $mutator): void
{
    global $appState;
    $mutator->mutate($appState);
}

/**
 * @param StateMutator $mutator
 */
function updateWithPhpDocType($mutator): void
{
    global $appState;
    $mutator->mutate($appState);
}

/**
 * @param StateMutator|null $mutator
 */
function updateWithNullablePhpDocType($mutator = null): void
{
    global $appState;
    $mutator?->mutate($appState);
}

class Updater
{
    /**
     * @param StateMutator $mutator
     */
    public function update($mutator): void
    {
        global $appState;
        $mutator->mutate($appState);
    }
}
