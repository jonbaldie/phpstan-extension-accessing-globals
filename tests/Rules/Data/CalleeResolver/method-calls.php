<?php

namespace CalleeResolverMethodFixtures;

class Receiver
{
    public function change(int &$value, int $count = 1): void
    {
    }
}

function calls(Receiver $receiver, ?Receiver $nullable, string $unknown): void
{
    $value = 0;
    $method = 'change';
    $receiver->change($value);

    $receiver->$method($value);
    $nullable?->change($value);
    $nullable?->$method($value);
    $receiver->$unknown($value);
    $receiver->missing($value);
}
