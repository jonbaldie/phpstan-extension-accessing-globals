<?php

declare(strict_types=1);

function mutateSuperglobalProperties(): void
{
    $_SESSION['state']->name = 'alice';
    $_SESSION['state']->count++;
    unset($_SESSION['state']->name);
}

function mutateGlobalsProperties(): void
{
    $GLOBALS['state']->name = 'bob';
    $GLOBALS['state']->count++;
    unset($GLOBALS['state']->name);
}

// Property writes in root scope are also global mutations.
$GLOBALS['root']->name = 'root';
$GLOBALS['root']->count++;
unset($GLOBALS['root']->name);
