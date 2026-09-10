<?php

declare(strict_types=1);

function popDeclaredItems(): void
{
    global $items;

    array_pop($items);
}

function popUndeclaredLocal(): void
{
    $stack = [1, 2, 3];

    array_pop($stack);
}
