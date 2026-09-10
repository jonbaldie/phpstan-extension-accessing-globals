<?php

function modifyGlobalDimensions()
{
    global $db;

    $db['key'] = 1;
    $db['outer']['inner'] = 2;
    $db['compound'] += 1;
    ++$db['preIncrement'];
    $db['postIncrement']++;
    --$db['preDecrement'];
    $db['postDecrement']--;
    $ref = &$db['reference'];
    ['key' => $db['destructured']] = ['key' => 1];
    $local['key'] = 1;
}
