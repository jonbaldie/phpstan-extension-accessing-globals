<?php

$_GET['root-plus'] += 1;
$_POST['root-postinc']++;

function modifyNestedSuperglobalsWithEveryMutationForm(): void
{
    $_GET['plus'] += 1;
    $_POST['concat'] .= 'x';
    $_REQUEST['postinc']++;
    --$_SESSION['predec'];
    ++$_COOKIE['preinc'];
    $_FILES['postdec']--;
    $_ENV['coalesce'] ??= 1;
    ['name' => $_SERVER['destructured']] = ['name' => 'changed'];
    $ref = &$GLOBALS['reference'];
}
