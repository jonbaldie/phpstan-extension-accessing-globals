<?php

namespace NamespaceFallback;

function run(): array
{
    return [
        time(),
        getenv('APP_ENV'),
    ];
}
