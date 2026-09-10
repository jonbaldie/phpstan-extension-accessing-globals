<?php

function issue16RuntimeDynamicGlobal(string $name): void
{
    global $$name;
    $$name = new stdClass();
    $local = new stdClass();
}
