<?php

function test()
{
    $GLOBALS["foo"] = "bar";
}

function testGlobalModification()
{
    global $db;
    $db = new stdClass();
}
