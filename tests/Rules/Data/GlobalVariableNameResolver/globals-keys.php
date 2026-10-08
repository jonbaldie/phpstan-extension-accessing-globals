<?php

const ISSUE_116_RESOLVER_KEY = 'config';

function globalsKeys(string $dynamic, bool $flag): void
{
    $GLOBALS['literal'];
    $GLOBALS[ISSUE_116_RESOLVER_KEY];
    $k = 'variable';
    $GLOBALS[$k];
    $GLOBALS[$flag ? 'a' : 'b'];
    $GLOBALS[$dynamic];
    $GLOBALS[0];
    $notGlobals['literal'];
}
