<?php

namespace ResolverDirect;

const SETTING = 1;
class External
{
    public const LIMIT = 10;
}
function readConstants(): void
{
    echo SETTING;
    echo External::LIMIT;
    echo true, FALSE, null;
    echo External::class;
    $value = 1 + 2;
}
