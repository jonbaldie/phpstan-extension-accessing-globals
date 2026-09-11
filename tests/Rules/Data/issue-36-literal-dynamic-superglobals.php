<?php

declare(strict_types=1);

function readLiteralDynamicSuperglobal(): mixed
{
    return ${'_GET'}['id'];
}

function mutateLiteralDynamicSuperglobal(): void
{
    ${'_SESSION'}['user'] = 'alice';
}

function mutateLiteralDynamicGlobals(): void
{
    ${'GLOBALS'}['cache'] = 'enabled';
}

function readRuntimeDynamicVariable(string $name): mixed
{
    return ${$name}['id'];
}

function mutateRuntimeDynamicVariable(string $name): void
{
    ${$name}['value'] = 'value';
}
