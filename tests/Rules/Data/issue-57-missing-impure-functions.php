<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue57;

function getKnownImpure(): int
{
    return time();
}

function getHighResolutionTime(): int
{
    return hrtime(true);
}

function getCurrentDirectory(): string|false
{
    return getcwd();
}

function findTempFiles(): array
{
    return glob('/tmp/*');
}

function listDirectory(string $path): array|false
{
    return scandir($path);
}

function getLastError(): array|null
{
    return error_get_last();
}

function getSapiName(): string|false
{
    return php_sapi_name();
}

function getIniSetting(string $key): string|false
{
    return ini_get($key);
}
