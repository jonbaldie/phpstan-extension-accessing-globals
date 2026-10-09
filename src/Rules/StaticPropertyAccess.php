<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * A static property fetch: `className` is a named class as written or a
 * resolved class name, and `propertyName` is `{expression}` when the name is
 * not statically known.
 */
final class StaticPropertyAccess
{
    public function __construct(
        public readonly string $className,
        public readonly string $propertyName,
    ) {
    }
}
