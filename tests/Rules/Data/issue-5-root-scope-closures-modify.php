<?php

namespace AccessingGlobals\Tests\Rules\Data;

// Ordinary root statements are allowed by the nested-scope rules.
$_SESSION['u'] = 'x';

// A root-level closure modifying a superglobal is a nested scope.
$mutate = function (): void {
    $_COOKIE['user'] = 'jane';
};

// A root-level arrow function modifying a superglobal is a nested scope.
$mutateArrow = fn (): void => $_SERVER['c'] = '1';