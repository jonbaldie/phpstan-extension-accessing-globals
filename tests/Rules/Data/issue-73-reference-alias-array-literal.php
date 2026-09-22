<?php

declare(strict_types=1);

function aliasCreationViaArrayLiteral(): void
{
    global $db;

    $alias = [&$db];
}

function writeThroughArrayLiteralAlias(): void
{
    global $db;

    $alias = [&$db];
    $alias[0] = 'mutated';
}

function writeThroughKeyedArrayLiteralAlias(): void
{
    global $db;

    $alias = ['conn' => &$db];
    $alias['conn'] = 'mutated';
}

function writeThroughNestedArrayLiteralReference(): void
{
    global $db;

    $nested = [[&$db]];
    $nested[0][0] = 'mutated';
}

function referenceToGlobalDimensionThroughArrayLiteral(): void
{
    global $db;

    $alias = [&$db['host']];
    $alias[0] = 'mutated';
}

function arrayLiteralWithoutReferenceDoesNotAlias(): void
{
    global $db;

    $snapshot = [$db];
    $snapshot[0] = 'safe copy';
}

function rebindingAliasVariableDoesNotMutateGlobal(): void
{
    global $db;

    $alias = [&$db];
    $alias = [1, 2];
    $alias[] = 3;
    $alias[0] = &$other;
    unset($alias[0]);
}
