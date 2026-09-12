<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data;

function issue47ArrowFunctionPropertyMutation(): void
{
    global $state;

    // Objects are captured as handles, so an arrow function mutates the
    // globally declared object in place.
    $increment = fn(): int => $state->counter++;
    $increment();
}

function issue47ByValueClosurePropertyMutation(): void
{
    global $state;

    $reset = function () use ($state): void {
        $state->status = 'ready';
        $state->items['seen'] = true;
        unset($state->items['seen']);
    };
    $reset();
}

function issue47ByValueClosureLocalWritesAreNotMutations(): void
{
    global $db;

    // A by-value capture copies the binding itself, so writing to the copy
    // or to its array dimensions never reaches the global.
    $local = function () use ($db): void {
        $db = 'local only';
        $db['key'] = 'local only';
    };
    $local();
}

function issue47ShadowingParametersAreNotGlobals(): void
{
    global $state;

    $shadowed = fn(\stdClass $state): int => $state->counter++;
    $shadowed(new \stdClass());
}

function issue47NestedArrowFunctionInsideClosure(): void
{
    global $state;

    $outer = function () use ($state): callable {
        return fn(): string => $state->status = 'nested';
    };
    $outer();
}
