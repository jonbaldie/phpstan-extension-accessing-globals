<?php

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

    $_GET['count'] += 1;
    $_POST['message'] .= '!';
    ++$_REQUEST['count'];
    --$_SESSION['count'];
    $_SESSION['cart']['count']++;
    $_COOKIE['prefs']['theme'] .= '-dark';
    $_ENV['flags']['beta'] ??= true;
}
