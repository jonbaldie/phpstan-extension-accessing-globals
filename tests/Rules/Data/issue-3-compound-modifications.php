<?php

function modifySuperglobals(): void
{
    $_SESSION['count'] += 1;
    $_SESSION['log'] .= 'x';
    $_GET['page']++;
    --$_POST['page'];
    ++$_REQUEST['page'];
    $_FILES['page']--;
    $_COOKIE['count'] ??= 1;
    ['name' => $_SERVER['name']] = ['name' => 'changed'];
    ['outer' => ['name' => $_SERVER['nested']]] = ['outer' => ['name' => 'changed']];
    $ref = &$_ENV['name'];
}

function modifyGlobals(): void
{
    $GLOBALS['plus'] += 1;
    $GLOBALS['concat'] .= 'x';
    $GLOBALS['postinc']++;
    --$GLOBALS['predec'];
    ++$GLOBALS['preinc'];
    $GLOBALS['postdec']--;
    $GLOBALS['coalesce'] ??= 1;
    ['name' => $GLOBALS['destructured']] = ['name' => 'changed'];
    $ref = &$GLOBALS['reference'];
}

function modifyGlobalVariable(): void
{
    global $db;

    $db += 1;
    $db .= 'x';
    $db++;
    --$db;
    ++$db;
    $db--;
    $db ??= 1;
    [$db] = [1];
    $ref = &$db;
}
