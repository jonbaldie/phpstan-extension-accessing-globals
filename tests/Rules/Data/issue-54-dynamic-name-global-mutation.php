<?php

function dynamicDeclareThenDynamicAssign(): void
{
    $name = 'runtimeVar';
    global ${$name};

    $$name = 'overwritten';
}

function fullyDynamicDeclareThenAssign(): void
{
    $key = 'someVar';
    global $$key;

    $$key = 'overwritten';
}

function knownGlobalDynamicAssign(): void
{
    global $db;

    $name = 'db';
    $$name = 'overwritten';
}
