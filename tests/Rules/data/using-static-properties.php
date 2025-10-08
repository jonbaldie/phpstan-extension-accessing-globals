<?php

namespace AccessingGlobals\Tests\Rules\Data;

class Config
{
    public static $value = 'default';
}

// Accessing in the global scope is not flagged by this rule.
Config::$value = 'production';

// Accessing inside a function should be flagged.
function doSomething()
{
    if (Config::$value === 'production') {
        // ...
    }
}

class MyProcessor
{
    public function process()
    {
        // Accessing inside a method should also be flagged.
        return Config::$value;
    }
}
