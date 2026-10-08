<?php

declare(strict_types=1);

function calleeResolverFunctionCalls(mixed $unknown): void
{
    time();

    $callable = 'time';
    $callable();

    time(...);

    $unknown();
}
