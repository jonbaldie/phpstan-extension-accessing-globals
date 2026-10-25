<?php

namespace AccessingGlobals\Tests\Rules\Data\Issue129;

class Client
{
    public const OWN = 1;

    public function external(Config $config): array
    {
        $class = Config::class;
        return [
            Config::TIMEOUT,
            $config::TIMEOUT,
            $class::TIMEOUT,
        ];
    }

    /** @param class-string<Config> $class */
    public function typedClassString(string $class): int
    {
        return $class::TIMEOUT;
    }

    public function own(Client $client): array
    {
        $class = Client::class;
        $lowercase = 'accessingglobals\\tests\\rules\\data\\issue129\\client';
        return [
            $this::OWN,
            $client::OWN,
            $class::OWN,
            $lowercase::OWN,
            cLiEnT::OWN,
            self::OWN,
            static::OWN,
        ];
    }

    /**
     * @param Config|Client $object
     * @param class-string<Config>|class-string<Client> $class
     */
    public function ambiguous($object, string $class): array
    {
        return [$object::TIMEOUT, $class::TIMEOUT];
    }

    public function unresolved($unknown, string $class): array
    {
        return [$unknown::TIMEOUT, $class::TIMEOUT];
    }

    public function excluded(Config $config, string $member, Priority $priority): array
    {
        $class = Config::class;
        $enum = Priority::class;
        return [
            $config::class,
            $class::class,
            $config::{$member},
            $class::{'TIMEOUT'},
            $priority::High,
            $enum::High,
        ];
    }
}

function external(Config $config): int
{
    return $config::TIMEOUT;
}

$closure = function (Config $config): int {
    return $config::TIMEOUT;
};
$arrow = fn (Config $config): int => $config::TIMEOUT;

$config = new Config();
$class = Config::class;
$rootObject = $config::TIMEOUT;
$rootClassString = $class::TIMEOUT;
