<?php

namespace AccessingGlobals\Tests\Rules\Data;

class Config
{
    public const TIMEOUT = 30;
}

class AnotherConfig
{
    public const RETRIES = 3;
}

class BaseClass
{
    public const BASE_CONSTANT = 'base';
}

class MyClass extends BaseClass
{
    public const MY_CONSTANT = 'my_value';

    public function process()
    {
        // BAD: Accessing constant from an external class
        $retries = AnotherConfig::RETRIES;

        // OK: Accessing own constant
        $myValue = self::MY_CONSTANT;

        // OK: Accessing parent constant
        $baseValue = parent::BASE_CONSTANT;

        // OK: `::class` syntax should be ignored
        $className = Config::class;

        return [$myValue, $baseValue, $retries, $className];
    }
}

function doSomethingExternal()
{
    // BAD: Accessing constant from an external class inside a function
    $timeout = Config::TIMEOUT;
    return $timeout;
}

// OK: Accessing constants in the global scope is not the concern of this rule.
$globalTimeout = Config::TIMEOUT;
$globalRetries = AnotherConfig::RETRIES;
