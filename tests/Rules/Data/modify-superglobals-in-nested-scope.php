<?php

// Modification in root scope is allowed
$_GET['foo'] = 'bar';
$_POST['foo'] = 'bar';

function test()
{
    $_GET['foo'] = 'bar';
    $_POST['foo'] = 'bar';
    $_REQUEST['foo'] = 'bar';
    $_SESSION['foo'] = 'bar';
    $_COOKIE['foo'] = 'bar';
    $_FILES['foo'] = 'bar';
    $_ENV['foo'] = 'bar';
    $_SERVER['foo'] = 'bar';
    $GLOBALS['foo'] = 'bar';
}
