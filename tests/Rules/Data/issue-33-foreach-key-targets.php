<?php

declare(strict_types=1);

$data = ['a' => 1];

foreach ($data as $_SESSION['key'] => $val) {
}

foreach ($data as $GLOBALS['key'] => $val) {
}

function writesGlobalKey(array $data): void
{
    global $globalDeclared;

    foreach ($data as $globalDeclared => $val) {
    }

    foreach ($data as $_POST['key'] => $val) {
    }
}
