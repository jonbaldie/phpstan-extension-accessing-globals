<?php

const APP_MODE = 'live';
class Settings
{
    public const LIMIT = 3;
    public static int $count = 0;
}

function hidden(): array
{
    $clock = time();
    $mode = APP_MODE;
    $limit = Settings::LIMIT;
    $count = Settings::$count;
    return [$clock, $mode, $limit, $count];
}

function explicit(int $clock, string $mode, int $limit, int $count): array
{
    return [$clock, $mode, $limit, $count];
}
