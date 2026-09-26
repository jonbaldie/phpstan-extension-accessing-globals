<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Config;

use PHPUnit\Framework\TestCase;

/**
 * Runs the real PHPStan binary against the opinionated ruleset with the
 * fixtures bundled together, as in the documented quick verification, so that
 * colliding fixture declarations cannot add unrelated diagnostics.
 */
class OpinionatedRulesetTest extends TestCase
{
    public function testQuickVerificationReportsOnlyExtensionDiagnostics(): void
    {
        $identifiers = $this->analyse(
            'config/rules-opinionated.neon',
            [
                __DIR__ . '/../Rules/Data/using-global-constants.php',
                __DIR__ . '/../Rules/Data/using-static-properties.php',
                __DIR__ . '/../Rules/Data/using-class-constants.php',
                __DIR__ . '/../Rules/Data/using-impure-functions.php',
            ],
        );

        $this->assertSame(
            [
                'constant.class' => 2,
                'constant.global' => 3,
                'function.impure' => 4,
                'property.static' => 2,
            ],
            $identifiers,
        );
    }

    /**
     * @param list<string> $paths
     * @return array<string, int> error counts keyed by identifier
     */
    private function analyse(string $config, array $paths): array
    {
        $root = dirname(__DIR__, 2);
        $command = [
            PHP_BINARY,
            $root . '/vendor/bin/phpstan',
            'analyse',
            '--configuration=' . $root . '/' . $config,
            '--level=0',
            '--no-progress',
            '--error-format=json',
            '--memory-limit=-1',
            ...$paths,
        ];

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        $this->assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $result = json_decode((string) $stdout, true);
        $this->assertIsArray($result, 'PHPStan did not produce JSON output: ' . $stderr);

        $identifiers = [];

        foreach ($result['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $identifiers[] = $message['identifier'];
            }
        }

        $counts = array_count_values($identifiers);
        ksort($counts);

        return $counts;
    }
}
