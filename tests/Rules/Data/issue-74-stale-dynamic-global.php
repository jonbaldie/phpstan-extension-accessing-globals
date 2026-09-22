<?php

declare(strict_types=1);

function probeDynamicFalseNegative(): void
{
    $name = 'db';
    $name .= '_suffix';
    global ${$name};

    $fresh = 'db_suffix';
    ${$fresh} = 1;
}

function probeWrongName(): void
{
    $name = 'db';
    $name .= '_suffix';
    global ${$name};

    ${$name} = 1;
}
