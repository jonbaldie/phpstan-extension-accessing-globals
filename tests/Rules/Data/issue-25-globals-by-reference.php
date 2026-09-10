<?php

declare(strict_types=1);

function popGlobalsConfig(): void
{
    array_pop($GLOBALS['config']);
}
