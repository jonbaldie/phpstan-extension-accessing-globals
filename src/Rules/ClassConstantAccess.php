<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * constant('Foo::BAR'): both halves are taken verbatim from the literal, so
 * either may be empty or carry a leading backslash.
 */
final class ClassConstantAccess implements ConstantAccess
{
    public function __construct(
        public readonly string $className,
        public readonly string $constantName,
    ) {
    }
}
