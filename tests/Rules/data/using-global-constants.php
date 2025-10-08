<?php

namespace AccessingGlobals\Tests\Rules\Data;

// Define some global constants
define('MY_CONSTANT', 'hello');
const ANOTHER_CONSTANT = 'world';

// Accessing them in the global scope is fine.
$a = MY_CONSTANT;
$b = ANOTHER_CONSTANT;
$c = true; // Should be ignored
$d = null; // Should be ignored

// Accessing them inside a function is a hidden dependency and should be flagged.
function myFunction()
{
    echo MY_CONSTANT;
    echo ANOTHER_CONSTANT;
}

class MyClass
{
    public function myMethod()
    {
        // This should also be flagged.
        return MY_CONSTANT;
    }
}
