<?php

namespace LocalOverride;

function time(): int
{
    return 456;
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
