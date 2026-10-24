<?php

function aliasAssign(): void
{
    global $config;
    $alias = &$config;
    $alias['debug'] = true;           // L7: modifies global $config through a reference alias
}

function byRefForeach(): void
{
    global $items;
    foreach ($items as &$item) {
        $item = 0;                    // L14: modifies global $items elements
    }
}

function closureUseByRef(): void
{
    global $counter;
    $inc = function () use (&$counter) {
        $counter++;                   // L22: modifies global $counter
    };
    $inc();
}

function superglobalAlias(): void
{
    $session = &$_SESSION;
    $session['user'] = 'mallory';     // L30: modifies $_SESSION through a reference alias
}

function globalsAlias(): void
{
    $g = &$GLOBALS['config'];
    $g = [];                          // L36: modifies global $config via $GLOBALS reference
}

function staticLocal(): int
{
    static $n = 0;                    // function-static, not global
    return ++$n;
}
