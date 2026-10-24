<?php

final class Client
{
    public const LIMIT = 10;

    public function ownConstant(): int
    {
        return Client::LIMIT;             // L9: own class, not reported
    }

    public function ownConstantDifferentCase(): int
    {
        return CLIENT::LIMIT;             // L14: same class (PHP class names are case-insensitive)
    }
}

var_dump((new Client())->ownConstantDifferentCase());
