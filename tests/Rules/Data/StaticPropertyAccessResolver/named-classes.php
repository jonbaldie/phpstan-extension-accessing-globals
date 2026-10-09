<?php

namespace StaticPropertyAccessResolverFixtures;

class Config
{
    public static $value = 'default';
}

function readNamedClass()
{
    return Config::$value;
}

class Child extends Config
{
    public function readKeywords()
    {
        return [self::$value, static::$value, parent::$value];
    }
}
