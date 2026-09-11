<?php

namespace AccessingGlobals\Tests\Rules\Data;

class Config
{
    public const TIMEOUT = 30;
}

class DynamicClassConstantTest
{
    public function readDynamicConstant(string $name)
    {
        return Config::{$name};
    }

    public function readDynamicExpr()
    {
        return Config::{'TIMEOUT'};
    }
}

function readDynamicClassConstant(string $name)
{
    return Config::{$name};
}
