<?php

function issue4ShadowingAndCapture(): void
{
    global $db;
    $fn = function (int $db): int {
        $db = 5;
        return $db;
    };
    $fn(1);
    $g = function () use ($db): void {
        $db = 'local only';
    };
    $g();
    $h = function () use (&$db): void {
        $db = 'mutates global';
    };
    $h();
    $db = 'outer write';
}
