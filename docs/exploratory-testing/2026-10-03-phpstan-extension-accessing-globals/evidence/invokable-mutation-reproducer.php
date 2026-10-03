<?php

declare(strict_types=1);

namespace AccessingGlobals\ExploratoryTesting;

class InvokableMutator
{
    public function __invoke(mixed &$data): void
    {
        if (is_array($data)) {
            $data['mutated'] = true;
        } else {
            $data = 'mutated';
        }
    }
}

function updateStateWithInvokable(InvokableMutator $mutator): void
{
    global $appState;

    // Emits access.global on line 20, but misses modify.global on line 23:
    $mutator($appState);

    // Emits access.superglobal.nested on line 26, but misses modify.superglobal.nested on line 26:
    $mutator($_SESSION);

    // Emits access.superglobal.nested on line 29, but misses modify.global and modify.superglobal.nested on line 29:
    $mutator($GLOBALS['config']);
}

function updateStateWithExplicitInvoke(InvokableMutator $mutator): void
{
    global $appState;

    // Correctly emits access.global and modify.global:
    $mutator->__invoke($appState);

    // Correctly emits access.superglobal.nested and modify.superglobal.nested:
    $mutator->__invoke($_SESSION);

    // Correctly emits access.superglobal.nested, modify.global, and modify.superglobal.nested:
    $mutator->__invoke($GLOBALS['config']);
}
