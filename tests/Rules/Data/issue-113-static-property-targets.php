<?php

namespace AccessingGlobals\Tests\Rules\Data\Issue113;

class Config
{
    public static $value = 'default';
}

class Reader
{
    public function readTypedOperands(Config $config)
    {
        $className = Config::class;
        $name = 'value';

        return [
            $config::$value,
            (new Config())::$value,
            $className::$value,
            Config::$$name,
        ];
    }
}
