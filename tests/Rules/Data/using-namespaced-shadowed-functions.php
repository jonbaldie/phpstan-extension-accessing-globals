<?php

namespace NamespacedShadowing;

function time(): int
{
    return 123;
}

function getenv(string $name): string
{
    return $name;
}

function run(): array
{
    return [
        time(),
        getenv('APP_ENV'),
    ];
}
