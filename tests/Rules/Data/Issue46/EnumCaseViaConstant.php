<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue46;

final class EnumCaseViaConstant
{
    public function describe(Priority $priority): string
    {
        // OK: Enum case via constant() is not a forbidden constant lookup
        if (constant(Priority::class . '::High') === $priority) {
            return 'Urgent priority';
        }

        // BAD: Real class constant via constant() is still forbidden
        return constant('AccessingGlobals\Tests\Rules\Data\Issue46\Priority::DEFAULT_PRIORITY');
    }
}
