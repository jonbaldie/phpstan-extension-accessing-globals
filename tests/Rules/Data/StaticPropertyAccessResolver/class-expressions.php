<?php

namespace StaticPropertyAccessResolverFixtures\Expressions;

class Config
{
    public static $value = 'default';

    public function readThis()
    {
        return $this::$value;
    }
}

class Other
{
    public static $value = 'other';
}

/**
 * @param class-string<Config> $className
 */
function readTypedOperands(Config $config, string $className)
{
    $classConstant = Config::class;

    return [$config::$value, (new Config())::$value, $className::$value, $classConstant::$value];
}

function readUnresolvableOperands($untyped, Config|Other $either, string $name)
{
    return [$untyped::$value, $either::$value, $name::$value];
}
