<?php

const ISSUE_116_DB_KEY = 'db';

function issue116Keys(string $dynamic, bool $flag): void
{
    $GLOBALS['db'] = 1;
    $k = 'db';
    $GLOBALS[$k] = 1;
    $GLOBALS[ISSUE_116_DB_KEY] = 1;
    $GLOBALS[$dynamic] = 1;
    $GLOBALS[$flag ? 'a' : 'b'] = 1;
    $GLOBALS[] = 1;
}
