<?php

declare(strict_types=1);

try {
    throw new \Exception('failure');
} catch (\Exception $_SESSION) {
}

function catchesIntoGlobalDeclared(): void
{
    global $error;

    try {
        throw new \Exception('failure');
    } catch (\Exception $error) {
    }
}

function catchesIntoSuperglobal(): void
{
    try {
        throw new \Exception('failure');
    } catch (\Exception $_GET) {
    }
}

function catchesIntoGlobalsArray(): void
{
    try {
        throw new \Exception('failure');
    } catch (\Exception $GLOBALS) {
    }
}

function catchesIntoLocalOrWithoutVariable(): void
{
    try {
        throw new \Exception('failure');
    } catch (\Exception $local) {
    }

    try {
        throw new \Exception('failure');
    } catch (\Exception) {
    }
}

function catchRebindsDynamicName(): void
{
    global $db;
    $name = 'db';

    try {
        throw new \Exception('failure');
    } catch (\Exception $name) {
    }

    $$name = 1;
}
