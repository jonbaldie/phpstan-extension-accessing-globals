<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\ByRefArgumentResolver;

final class InvokableMutator
{
    public function __invoke(array &$target, array $value): void
    {
    }
}

final class NotInvokable
{
}

final class Holder
{
    public function __construct(public InvokableMutator $mutator)
    {
    }
}

function callableExpressionCalls(InvokableMutator $mutator, Holder $holder, NotInvokable $object, mixed $unknown, array $a, array $b): void
{
    $mutator($a, $b);
    $mutator(value: $a, target: $b);
    $holder->mutator($a, $b);
    ($holder->mutator)($b, $a);
    $closure = function (array $value, array &$target): void {
    };
    $closure($a, $b);
    $arrow = fn (array &$target) => $target;
    $arrow($a);
    $object($a);
    $unknown($a);
}
