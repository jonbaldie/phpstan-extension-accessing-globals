<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue125;

class Client
{
    public const OWN_TIMEOUT = 20;

    public function process(): void
    {
        // OK: a known name on the current class keeps the self:: exclusion
        $own = 'self::OWN_TIMEOUT';
        constant($own);

        // OK: a known enum case name keeps the enum-case exclusion
        $case = Priority::class . '::High';
        constant($case);

        // OK: a known ::class name keeps the ::class exclusion
        $className = 'AccessingGlobals\Tests\Rules\Data\Issue125\Config::class';
        constant($className);
    }
}

function readKnownNames(string $unknown, bool $flag): void
{
    // BAD: a variable holding a known external class-constant name
    $classConstant = 'AccessingGlobals\Tests\Rules\Data\Issue125\Config::TIMEOUT';
    constant($classConstant);

    // BAD: a variable holding a known global-constant name
    $globalConstant = 'PHP_VERSION';
    constant($globalConstant);

    // BAD: genuinely unknown and ambiguous names stay dynamic
    constant($unknown);
    constant($flag ? 'PHP_VERSION' : 'PHP_OS');
}

// OK: known names at the root scope remain allowed
$rootName = 'AccessingGlobals\Tests\Rules\Data\Issue125\Config::TIMEOUT';
constant($rootName);
