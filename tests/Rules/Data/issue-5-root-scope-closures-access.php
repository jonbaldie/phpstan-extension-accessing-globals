<?php

namespace AccessingGlobals\Tests\Rules\Data;

// Ordinary root statements are allowed by the nested-scope rules.
$_GET['q'];

// A root-level closure reading a superglobal is a nested scope.
$fn = function (): void {
    echo $_POST['q'];
};

// A root-level arrow function reading a superglobal is a nested scope.
$arrow = fn (): int => count($_SESSION);