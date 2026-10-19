<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data;

class Box107
{
    /** @param array<mixed> $a */
    public function fill(array &$a): void
    {
        $a[] = 1;
    }
}

class State107
{
    /** @var array<mixed> */
    public array $items = [];
}

/** @param list<Box107> $boxes */
function viaForeach(array $boxes): void
{
    global $db;
    foreach ($boxes as $box) {
        $box->fill($db);
    }
}

/** @param list<Box107> $boxes */
function viaDestructuring(array $boxes): void
{
    global $db;
    [$box] = $boxes;
    $box->fill($db);
}

function viaClosureParam(): void
{
    global $db;
    $f = function (Box107 $box) use (&$db): void {
        $box->fill($db);
    };
}

function viaPlainAssign(): void
{
    global $db;
    $box = new Box107();
    $box->fill($db);
}

function viaStaticVar(): void
{
    global $db;
    /** @var Box107 $box */
    static $box;
    $box->fill($db);
}

function viaArrowParam(): void
{
    global $state;
    $f = fn (Box107 $box): mixed => $box->fill($state->items);
}

/** @param list<Box107> $boxes */
function viaLocalVar(array $boxes): void
{
    $local = [];
    foreach ($boxes as $box) {
        $box->fill($local);
    }
}
