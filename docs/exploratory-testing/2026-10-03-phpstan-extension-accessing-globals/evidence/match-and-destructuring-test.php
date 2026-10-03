<?php

declare(strict_types=1);

namespace AccessingGlobals\ExploratoryTesting;

function testMatch(string $action): mixed
{
    global $count;

    return match ($action) {
        'read' => $_SESSION['count'],
        'write_session' => $_SESSION['count'] = 1,
        'write_global' => $count = 1,
        default => null,
    };
}

function testDestructuring(): void
{
    global $a, $b;

    [[$a], 'key' => $b] = [[1], 'key' => 2];
}
