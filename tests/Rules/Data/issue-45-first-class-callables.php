<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue45;

function fetchAll(array $urls): array
{
    return array_map(file_get_contents(...), $urls);
}

function getRandomNumber(): int
{
    $generator = rand(...);

    return $generator(1, 100);
}

function getSystemTime(): int
{
    return (time(...))();
}

function getEnvironmentVar(string $name): ?string
{
    $env = getenv(...);

    return $env($name) ?: null;
}

function getLength(string $s): int
{
    $strlen = strlen(...);

    return $strlen($s);
}
