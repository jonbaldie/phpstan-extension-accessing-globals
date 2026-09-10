<?php

declare(strict_types=1);

function modifyGlobal(): void
{
    global $db;

    foreach ([1] as &$db) {
    }
}
