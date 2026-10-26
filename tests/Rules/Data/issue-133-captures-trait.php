<?php
trait Issue133CapturesTrait
{
    public function captures(): void
    {
        global $db, $state;
        $byReference = function () use (&$db): void {
            $db = 2;
        };
        $byValue = function () use ($db): void {
            $db = 3;
        };
        $object = function () use ($state): void {
            $state->value = 4;
        };
        $nestedArrows = fn() => fn() => $state->value = 5;
        $shadowed = fn($state) => fn() => $state->value = 6;
        $aliases = [&$db];
        $arrowAlias = fn() => $aliases[0] = 7;
    }
}
