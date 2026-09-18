<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data;

final class ConstructorByRefMutator
{
    /**
     * @param mixed $data
     */
    public function __construct(mixed &$data)
    {
    }
}

final class ConstructorReadOnly
{
    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
    }
}

final class ConstructorWithoutParams
{
    public function __construct()
    {
    }
}

// Line 32: Root scope: mutates $GLOBALS['config']
new ConstructorByRefMutator($GLOBALS['config']);

// Root scope by-value: must not be flagged
new ConstructorReadOnly($GLOBALS['config']);
new ConstructorWithoutParams();

function mutateInNestedScope(): void
{
    // Line 41: Nested scope: mutates $_SESSION
    new ConstructorByRefMutator($_SESSION['user']);

    // Line 44: Nested scope: mutates $GLOBALS
    new ConstructorByRefMutator($GLOBALS['database']);

    // Line 47: Named argument invocation: mutates $_SESSION
    new ConstructorByRefMutator(data: $_SESSION['user']);

    // Dynamic class string
    $class = ConstructorByRefMutator::class;
    // Line 52: Dynamic class mutates $_SESSION
    new $class($_SESSION['user']);
    // Line 54: Dynamic class mutates $GLOBALS
    new $class($GLOBALS['config']);

    // Anonymous class with by-reference constructor
    // Line 58: Anonymous class mutates $GLOBALS
    new class($GLOBALS['anon']) {
        public function __construct(mixed &$data)
        {
        }
    };

    // By-value calls: must not be reported as mutations
    new ConstructorReadOnly($_SESSION['user']);
    new ConstructorReadOnly($GLOBALS['database']);
    new ConstructorWithoutParams();
}

function mutateGloballyDeclared(): void
{
    global $state;

    // Line 74: Declared global variable
    new ConstructorByRefMutator($state);

    // Dynamic class declared global variable
    $class = ConstructorByRefMutator::class;
    // Line 79: Dynamic class declared global variable
    new $class($state);

    // Anonymous class declared global variable
    // Line 83: Anonymous class declared global variable
    new class($state) {
        public function __construct(mixed &$data)
        {
        }
    };

    // By-value call: must not be reported as mutation
    new ConstructorReadOnly($state);
}
