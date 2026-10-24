<?php

namespace Shop;

final class Config
{
    public const TIMEOUT = 30;
    public static int $hits = 0;
}

interface HasLimit
{
    public const LIMIT = 10;
}

final class Client implements HasLimit
{
    public function viaInstance(Config $config): int
    {
        return $config::TIMEOUT;                  // L20: external class constant through an instance
    }

    public function viaClassString(): int
    {
        $cls = Config::class;                     // L25: ::class is fine
        return $cls::TIMEOUT;                     // L26: external class constant through class-string
    }

    public function ownInterface(): int
    {
        return self::LIMIT + HasLimit::LIMIT;     // L31: self ok; HasLimit::LIMIT is the class's own interface
    }

    public function staticViaInstance(Config $config): int
    {
        return $config::$hits;                    // L36: static property through instance
    }

    public function impure(): string
    {
        $now = \date('Y-m-d');                    // L41: fully-qualified impure call in namespace
        $r = \call_user_func('rand');             // L42: impure via call_user_func string
        $m = array_map('microtime', [true]);      // L43: impure callable string passed to array_map
        $o = new \DateTimeImmutable();            // L44: clock read via constructor
        return $now . $r . $m[0] . $o->format('c');
    }
}
