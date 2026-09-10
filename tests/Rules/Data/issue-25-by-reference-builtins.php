<?php

declare(strict_types=1);

function popSessionItems(): void
{
    array_pop($_SESSION['items']);
}

function shiftGetQueue(): void
{
    array_shift($_GET['queue']);
}

function pushIntoPostList(): void
{
    array_push($_POST['list'], 'new-item');
}

function sortCookieValues(): void
{
    sort($_COOKIE['values']);
}

function castRequestType(): void
{
    settype($_REQUEST['id'], 'int');
}

function popGlobalsItems(): void
{
    array_pop($GLOBALS['items']);
}

function readLocalArray(array $config): void
{
    array_pop($config);
}

function callByValueWithSuperglobal(): void
{
    count($_SERVER);
}

array_pop($_ENV['path']);
