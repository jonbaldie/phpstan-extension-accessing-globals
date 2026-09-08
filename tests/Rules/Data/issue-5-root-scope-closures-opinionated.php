<?php

namespace AccessingGlobals\Tests\Rules\Data;

define('ISSUE_FIVE_CONST', 1);

class IssueFiveConfig
{
    public const BAR = 1;

    public static $prop = 2;
}

// Root-level code is not these rules' concern.
$x = ISSUE_FIVE_CONST;
$y = IssueFiveConfig::BAR;
$z = IssueFiveConfig::$prop;
time();

// A root-level closure is a nested scope for the opinionated rules.
$fn = function (): int {
    return time() + ISSUE_FIVE_CONST + IssueFiveConfig::BAR + IssueFiveConfig::$prop;
};

// A root-level arrow function is a nested scope too.
$arrow = fn (): int => rand();