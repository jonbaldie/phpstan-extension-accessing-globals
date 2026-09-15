<?php

declare(strict_types=1);

function positionalSession(): void
{
    array_multisort($_SESSION['m']);
}

function namedSession(): void
{
    array_multisort(array: $_SESSION['m']);
}

function extraArrays(): void
{
    array_multisort($_SESSION['a'], $_GET['b']);
}

function positionalGlobals(): void
{
    array_multisort($GLOBALS['m']);
}

function namedGlobals(): void
{
    array_multisort(array: $GLOBALS['m']);
}

function localOnly(array $items): void
{
    array_multisort($items);
}

function controlSort(): void
{
    sort($_SESSION['m']);
}

function declaredGlobal(): void
{
    global $items;

    array_multisort($items);
}

array_multisort($_COOKIE['values']);
