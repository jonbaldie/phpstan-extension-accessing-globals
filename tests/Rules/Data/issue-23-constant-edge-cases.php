<?php

namespace AccessingGlobals\Tests\Rules\Data\Issue23Shadow;

const ISSUE_TWENTY_THREE_EDGE_CONST = 'edge';

function shadowedConstant(string $name): string
{
    // Not PHP's builtin constant(): this namespace declares its own.
    return constant($name);
}

function constant(string $name): string
{
    return 'shadowed';
}

namespace AccessingGlobals\Tests\Rules\Data\Issue23Edge;

use function constant;

const ISSUE_TWENTY_THREE_EDGE_CONST = 'edge';

function readsClassConstantViaConstant(): string
{
    // constant('Foo::BAR') reads a class constant, not a global one.
    return constant('Some\Other\Class::ISSUE_TWENTY_THREE_CLASS_CONST');
}

function readsGlobalConstantFullyQualified(): string
{
    // The fully qualified form is still PHP's builtin constant().
    return \constant('ISSUE_TWENTY_THREE_EDGE_CONST');
}