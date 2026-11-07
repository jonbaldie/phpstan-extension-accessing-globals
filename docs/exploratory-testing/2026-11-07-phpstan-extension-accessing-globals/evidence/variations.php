<?php

class Limit { public const MAX = 3; }

function mutate(array &$target): void { $target['visited'] = true; }

function nested(): void
{
    mutate($_SESSION);
    $clock = 'time';
    $value = $clock();
    $constant = 'constant';
    $mode = $constant('APP_MODE');
    $class = Limit::class;
    $max = $class::MAX;
}
