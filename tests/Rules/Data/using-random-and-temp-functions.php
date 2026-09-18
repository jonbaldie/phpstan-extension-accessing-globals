<?php

namespace AccessingGlobals\Tests\Rules\Data;

function testRandomFunctions(): void
{
    $items = ['apple', 'banana', 'cherry'];

    shuffle($items); // Impure: mutates array based on internal PRNG state

    $key = array_rand($items); // Impure: picks random keys based on internal PRNG state

    $shuffled = str_shuffle('abcdef'); // Impure: random permutation of a string
}

function testTempFilesystemFunctions(): void
{
    $tempDir = sys_get_temp_dir(); // Impure: reads system temp dir

    $tempFile = tempnam($tempDir, 'test_'); // Impure: creates a temporary file on the filesystem

    $fh = tmpfile(); // Impure: creates a temporary file resource
}
