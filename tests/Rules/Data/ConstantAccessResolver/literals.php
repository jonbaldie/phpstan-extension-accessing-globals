<?php

declare(strict_types=1);

function constantAccessResolverLiterals(): void
{
    constant('FOO');
}

function constantAccessResolverNotConstantLookups(string $functionName): void
{
    strlen('FOO');
    $functionName('FOO');
    constant();
}

function constantAccessResolverQualifiedLiterals(): void
{
    constant('\Ns\FOO');
    constant('Foo::BAR');
    constant('\Ns\Foo::BAR');
}

function constantAccessResolverDynamicName(string $dynamic): void
{
    constant($dynamic);
    constant('Foo' . '::BAR');
}
