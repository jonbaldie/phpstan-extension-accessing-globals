<?php

function unsetGlobalBinding(): void
{
    global $db;

    unset($db);
}
