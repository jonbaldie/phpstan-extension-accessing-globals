<?php

declare(strict_types=1);

namespace Issue75;

function idate(string $format): string
{
    return $format;
}

function example(): void
{
    idate('Y');
    \idate('Y');
    \IDATE('Y');
    strlen('pure');
}
