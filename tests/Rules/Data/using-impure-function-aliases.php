<?php

namespace AliasedImpureFunctions;

use function getenv as readEnvironment;
use function time as currentTime;

function run(): array
{
    return [
        currentTime(),
        readEnvironment('APP_ENV'),
    ];
}
