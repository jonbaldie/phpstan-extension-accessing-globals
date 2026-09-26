<?php

namespace AccessingGlobals\Tests\Rules\Data;

class StaticPropertyConfig
{
    public static $value = 'default';
}

// Accessing in the global scope is not flagged by this rule.
StaticPropertyConfig::$value = 'production';

// Accessing inside a function should be flagged.
function doSomething()
{
    if (StaticPropertyConfig::$value === 'production') {
        // ...
    }
}

class MyProcessor
{
    public function process()
    {
        // Accessing inside a method should also be flagged.
        return StaticPropertyConfig::$value;
    }
}
