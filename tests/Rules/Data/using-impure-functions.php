<?php

namespace AccessingGlobals\Tests\Rules\Data;

// Calling impure functions in the global scope is not the concern of this rule.
$a = time();
$b = getenv('PATH');

// This function calls several impure functions and should be flagged.
function generateId()
{
    $timestamp = time(); // Impure: time-dependent
    $random = rand();    // Impure: depends on RNG state
    $env = getenv('USER'); // Impure: depends on environment
    return "{$timestamp}-{$random}-{$env}";
}

class DataProcessor
{
    public function loadData(string $path)
    {
        // Impure: depends on filesystem state
        return file_get_contents($path);
    }
}

// This function calls a pure function and should NOT be flagged.
function getLength(string $s)
{
    return strlen($s);
}
