<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue46;

enum Priority: string
{
    case Low = 'low';
    case High = 'high';

    public const DEFAULT_PRIORITY = 'low';
}
