<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue115Constant;

function constantCallableExpressions(): void
{
    $constant = 'constant';
    $constant('MY_CONSTANT');

    $constantCallable = constant(...);
    $constantCallable('MY_CONSTANT');
}
