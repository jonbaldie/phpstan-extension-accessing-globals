<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue67;

class DatabaseConnection
{
}

function configureConnection(?DatabaseConnection $conn): void
{
    global $globalConnection;

    $globalConnection = $conn;
}
