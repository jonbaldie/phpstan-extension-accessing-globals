<?php

namespace StaticPropertyAccessResolverFixtures\PropertyNames;

class Config
{
    public static $value = 'default';
}

function readPropertyNameExpressions(string $unknown, bool $flag)
{
    $name = 'value';
    $either = $flag ? 'value' : 'other';

    return [Config::$$name, Config::${'value'}, Config::$$unknown, Config::$$either];
}
