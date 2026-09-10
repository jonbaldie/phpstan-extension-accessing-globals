<?php

declare(strict_types=1);

function mutateSuperglobalElementsByReference(): void
{
    foreach ($_SESSION as &$value) {
        $value = 'changed';
    }
}

function mutateGlobalsElementsByReference(): void
{
    foreach ($GLOBALS as &$pair) {
        $pair = 'changed';
    }
}

function mutateServerByKeyAndReference(): void
{
    foreach ($_SERVER as $key => &$config) {
        $config = 'changed';
    }
}

foreach ($_GET as &$setting) {
    $setting = 'changed';
}

function iterateSuperglobalWithoutReference(): void
{
    foreach ($_COOKIE as $cookie) {
    }
}

function iterateLocalArrayByReference(array $config): void
{
    foreach ($config as &$value) {
        $value = 'changed';
    }
}
