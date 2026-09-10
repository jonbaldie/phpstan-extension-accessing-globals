<?php

function issue16LiteralDynamicGlobal(): void
{
    global ${'db'};
    ${'db'} = new stdClass();
}
