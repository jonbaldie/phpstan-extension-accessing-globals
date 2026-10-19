<?php

namespace CalleeResolverFunctionFixtures;

function change(int &$value, int $count = 1): void
{
}

class Invokable
{
    public function __invoke(int &$invokedValue): void
    {
    }
}

function calls(Invokable $invokable): void
{
    $value = 0;
    change($value);
    $function = __NAMESPACE__ . '\\change';
    $function($value);
    $closure = static function (int &$closedValue): void {};
    $closure($value);
    $invokable($value);
    missing($value);
    $invalid = 42;
    $invalid($value);
}
