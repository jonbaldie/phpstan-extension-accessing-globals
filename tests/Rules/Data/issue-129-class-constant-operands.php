<?php

namespace AccessingGlobals\Tests\Rules\Data\Issue129;

function byInstance(Config $config): int
{
    return $config::TIMEOUT;
}

function byClassString(): int
{
    $class = Config::class;
    return $class::TIMEOUT;
}
