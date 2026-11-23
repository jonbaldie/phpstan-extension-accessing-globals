<?php

$root = 1;
function scopeFunction(): void { $function = 1; }
class ScopeClassifierFixture { public function method(): void { $method = 1; } }
$closure = function (): void { $insideClosure = 1; };
$arrow = fn () => $insideArrow;
