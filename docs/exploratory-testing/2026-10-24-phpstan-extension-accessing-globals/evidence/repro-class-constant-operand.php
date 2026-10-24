<?php

final class Config
{
    public const TIMEOUT = 30;
    public static int $hits = 0;
}

final class Client
{
    public function byName(): int
    {
        return Config::TIMEOUT;           // L13: reported
    }

    public function byInstance(Config $config): int
    {
        return $config::TIMEOUT;          // L18: same constant, not reported
    }

    public function byClassString(): int
    {
        $cls = Config::class;
        return $cls::TIMEOUT;             // L24: same constant, not reported
    }

    public function staticPropertyByInstance(Config $config): int
    {
        return $config::$hits;            // L29: sibling rule reports the instance form
    }
}
