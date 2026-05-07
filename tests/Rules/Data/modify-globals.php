<?php

function test()
{
    $GLOBALS["foo"] = "bar";
    $GLOBALS["config"]["dsn"] = "mysql";
    $GLOBALS["count"] += 1;
    $GLOBALS["message"] .= "!";
    ++$GLOBALS["config"]["retries"];
    --$GLOBALS["config"]["retries"];
}

function testGlobalModification()
{
    global $db;
    $db = new stdClass();
}
