<?php

namespace ClassOperandResolverFixtures;

class Config
{
    public static function make()
    {
    }
}

class Other
{
    public static function make()
    {
    }
}

class Child extends Config
{
    public function readKeywords()
    {
        return [self::make(), static::make(), parent::make(), $this::make()];
    }
}

/**
 * @param class-string<Config> $className
 */
function readTypedOperands(Config $config, string $className)
{
    $classConstant = Config::class;

    return [Config::make(), $config::make(), (new Config())::make(), $className::make(), $classConstant::make()];
}

function readUnionOperands(Config|Other $either)
{
    return $either::make();
}

function readNonClassOperands($untyped, string $name, int $number)
{
    return [$untyped::make(), $name::make(), $number::make()];
}
