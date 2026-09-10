<?php

const ISSUE_16_GLOBAL_NAME = 'db';

function issue16ConstantDynamicGlobal(): void
{
    global ${ISSUE_16_GLOBAL_NAME};
    $db = new stdClass();
}
