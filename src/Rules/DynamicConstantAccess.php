<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * constant($name): the name is not a string literal, so the lookup may
 * resolve to either a global or a class constant at runtime.
 */
final class DynamicConstantAccess implements ConstantAccess
{
}
