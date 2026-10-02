<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\ByRefArgumentResolver;

final class NullsafeMutator
{
    public function mutate(array &$target, array $value): void
    {
    }
}

function nullsafeMethodCalls(?NullsafeMutator $mutator, NullsafeMutator $present, array $a, array $b): void
{
    $mutator?->mutate($a, $b);
    $present?->mutate(value: $a, target: $b);
    $mutator?->mutate($a, $b)?->undefinedMethod($b);
}
