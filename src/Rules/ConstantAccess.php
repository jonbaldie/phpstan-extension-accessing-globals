<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * A constant lookup performed through a builtin constant() call: one of
 * GlobalConstantAccess, ClassConstantAccess or DynamicConstantAccess.
 */
interface ConstantAccess
{
}
