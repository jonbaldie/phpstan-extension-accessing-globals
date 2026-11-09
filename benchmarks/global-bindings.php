<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
if (getenv('BENCH_RULE')) {
    require getenv('BENCH_RULE');
}

use AccessingGlobals\Rules\NeverModifyGloballyDeclaredVariablesRule;

$n = filter_var($argv[1] ?? '1000', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($n === false) {
    fwrite(STDERR, "Usage: php benchmarks/global-bindings.php <positive integer>\n");
    exit(1);
}

$facts = [['kind' => 'scope', 'scope' => ['id' => 'r', 'parent' => null, 'type' => 'function', 'params' => [], 'uses' => []]]];
for ($i = 0; $i < $n; $i++) {
    $facts[] = ['kind' => 'global', 'scopeId' => 'r', 'names' => ['g' . $i], 'pos' => $i];
}
for ($i = 0; $i < $n; $i++) {
    $facts[] = ['kind' => 'mutation', 'scopeId' => 'r', 'targetName' => 'local' . $i, 'throughObjectHandle' => false, 'outermostSlotKeys' => [], 'pos' => $n + $i];
}
$facts[] = ['kind' => 'mutation', 'scopeId' => 'r', 'targetName' => 'g0', 'throughObjectHandle' => false, 'outermostSlotKeys' => [], 'pos' => 2 * $n, 'file' => __FILE__, 'description' => __FILE__, 'line' => 1];

$method = new ReflectionMethod(NeverModifyGloballyDeclaredVariablesRule::class, 'processFacts');
$errors = [];
$start = hrtime(true);
$method->invokeArgs(new NeverModifyGloballyDeclaredVariablesRule(), [$facts, &$errors]);
$seconds = (hrtime(true) - $start) / 1e9;
if (count($errors) !== 1 || $errors[0]->getIdentifier() !== 'modify.global' || $errors[0]->getLine() !== 1 || $errors[0]->getMessage() !== 'Code is modifying variable $g0 that was declared with the "global" keyword. Use dependency injection instead.') {
    fwrite(STDERR, "Unexpected diagnostics\n");
    exit(1);
}
printf("n=%d seconds=%.6f peak_mib=%.1f diagnostics=%d\n", $n, $seconds, memory_get_peak_usage(true) / 1048576, count($errors));
