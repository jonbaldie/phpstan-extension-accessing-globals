<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * constant('FOO'): the name is reported exactly as written.
 */
final class GlobalConstantAccess implements ConstantAccess
{
    public function __construct(
        public readonly string $constantName,
    ) {
    }
}
