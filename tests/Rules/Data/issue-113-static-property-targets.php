<?php

namespace AccessingGlobals\Tests\Rules\Data\Issue113;

class Config
{
    public static $value = 'default';

    public function readSelf()
    {
        return self::$value;
    }
}

function readTypedOperand(Config $config)
{
    return $config::$value;
}
