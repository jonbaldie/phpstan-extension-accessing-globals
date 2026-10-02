<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\ByRefArgumentResolver;

function fill(array &$target, int $value): void
{
}

function fillAll(int $value, array &...$targets): void
{
}

function functionCalls(array $a, array $b, array $c, array $rest): void
{
    preg_match('/x/', 'x', $a);
    strlen('by value');
    fill($a, 1);
    fill(value: 1, target: $b);
    fillAll(1, $a, $b, $c);
    fillAll(1, ...$rest);
    array_multisort($a, SORT_DESC, $b);
    undefinedFunction($a);
    $callable = 'fill';
    $callable($c, 1);
}
