<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue104;

final class InvokableMutator
{
    public function __invoke(array &$data): void
    {
    }
}

function updateStateWithInvokable(InvokableMutator $mutator): void
{
    global $appState;

    $mutator($appState);
    $mutator($_SESSION);
    $mutator($GLOBALS['config']);
}
