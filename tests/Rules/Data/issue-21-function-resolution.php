<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue21;

use function time as importedGlobalTime;

function time(): int
{
    return 42;
}

function callsNamespacedFunction(): int
{
    return time();
}

function callsExplicitGlobalFunction(): int
{
    return \time();
}

function callsImportedGlobalFunction(): int
{
    return importedGlobalTime();
}
