<?php

function outer(): void
{
    $fn = function (): void {
        global $db;
        $db = 1;
    };
    $fn();
}