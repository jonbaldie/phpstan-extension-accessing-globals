<?php

function mutatePropertyOnGlobalObject(): void
{
    global $box;

    $box->value = 1;
    $box->value += 1;
    unset($box->value);
}
