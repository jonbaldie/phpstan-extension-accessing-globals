<?php

namespace CalleeResolverStaticFixtures;

class Receiver
{
    public static function change(int &$value, int $count = 1): void
    {
    }
}

/** @param class-string<Receiver> $class */
function calls(Receiver $receiver, string $class, string $unknown): void
{
    $value = 0;
    $method = 'change';
    Receiver::change($value);
    Receiver::$method($value);
    $receiver::change($value);
    $class::change($value);
    Receiver::$unknown($value);
    Receiver::missing($value);
    $invalid = 42;
    $invalid::change($value);
}
