<?php

namespace CalleeResolverConstructorFixtures;

class Receiver
{
    public function __construct(int &$value, int $count = 1)
    {
    }
}

class WithoutConstructor
{
}

/** @param class-string<Receiver> $class */
function calls(Receiver $receiver, string $class): void
{
    $value = 0;
    new Receiver($value);
    new $receiver($value);
    new $class($value);
    new class ($value) {
        public function __construct(int &$anonymousValue)
        {
        }
    };
    new WithoutConstructor();
    $invalid = 42;
    new $invalid();
}
