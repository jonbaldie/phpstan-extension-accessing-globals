<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue98;

final class SessionMutator
{
    public function mutate(array &$data): void
    {
    }
}

function updateSession(?SessionMutator $mutator): void
{
    $mutator?->mutate($_SESSION['items']);
}
