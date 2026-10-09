<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

final class StaticPropertyAccess
{
    public function __construct(
        public readonly string $className,
        public readonly string $propertyName,
    ) {
    }
}
