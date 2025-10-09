<?php

// Access in root scope is allowed
$a = $_GET;
$b = $_POST;

function test()
{
    $get = $_GET;
    $post = $_POST;
    $request = $_REQUEST;
    $session = $_SESSION;
    $cookie = $_COOKIE;
    $files = $_FILES;
    $env = $_ENV;
    $server = $_SERVER;
    $globals = $GLOBALS;
}
