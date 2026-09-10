<?php

declare(strict_types=1);

namespace Exploratory\FunctionShadow;

function time(): int
{
    return 42;
}

function callsLocalFunction(): int
{
    return time();
}
