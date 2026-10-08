<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue115Impure;

function time(): int
{
    return 42;
}

function callableStringAndUnknownCalls(mixed $unknown): void
{
    time();

    $callable = 'time';
    $callable();

    $unknown();
}
