<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\ByRefArgumentResolver;

final class Mutator
{
    public function __construct(?array &$target = null)
    {
    }

    public function mutate(array &$target, array $value): void
    {
    }

    public static function mutateStatically(array $value, array &$target): void
    {
    }
}

function methodCalls(Mutator $mutator, array $a, array $b, string $className): void
{
    $mutator->mutate($a, $b);
    $mutator->mutate(value: $a, target: $b);
    $method = 'mutate';
    $mutator->$method($a, $b);
    Mutator::mutateStatically($a, $b);
    $mutator::mutateStatically($b, $a);
    new Mutator($a);
    new $mutator($b);
    new class ($a) {
        public function __construct(array &$target)
        {
        }
    };
    $mutator->undefinedMethod($a);
}
