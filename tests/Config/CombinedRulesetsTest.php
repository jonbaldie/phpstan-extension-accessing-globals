<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Config;

use PHPUnit\Framework\TestCase;

class CombinedRulesetsTest extends TestCase
{
    public function testBasicAndStrictRulesetsCanBeLoadedTogetherWithoutDuplicatingGlobalDiagnostics(): void
    {
        $root = dirname(__DIR__, 2);
        $tempDirectory = sys_get_temp_dir() . '/phpstan-combined-rulesets-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($tempDirectory));

        $config = $tempDirectory . '/combined.neon';
        file_put_contents(
            $config,
            sprintf(
                "includes:\n    - %s/config/rules.neon\n    - %s/config/rules-strict.neon\n",
                $root,
                $root,
            ),
        );

        try {
            $diagnostics = $this->analyse(
                $config,
                [
                    __DIR__ . '/../Rules/Data/access-globals.php',
                    __DIR__ . '/../Rules/Data/modify-globals.php',
                ],
            );
        } finally {
            unlink($config);
            rmdir($tempDirectory);
        }

        $this->assertSame(
            [
                'access-globals.php:10:access.global' => 2,
                'access-globals.php:5:access.global' => 1,
                'modify-globals.php:10:access.global' => 1,
                'modify-globals.php:11:modify.global' => 1,
                'modify-globals.php:5:modify.global' => 1,
            ],
            $diagnostics,
        );
    }

    /**
     * @param list<string> $paths
     * @return array<string, int> diagnostic counts keyed by file, line, and identifier
     */
    private function analyse(string $config, array $paths): array
    {
        $root = dirname(__DIR__, 2);
        $command = [
            PHP_BINARY,
            $root . '/vendor/bin/phpstan',
            'analyse',
            '--configuration=' . $config,
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
        $exitCode = proc_close($process);

        $result = json_decode((string) $stdout, true);
        $this->assertIsArray(
            $result,
            sprintf('PHPStan exited with %d instead of producing JSON output: %s', $exitCode, $stderr),
        );
        $this->assertSame(1, $exitCode, 'PHPStan should report the expected fixture violations.');

        $diagnostics = [];

        foreach ($result['files'] as $file => $analysis) {
            foreach ($analysis['messages'] as $message) {
                if (!in_array($message['identifier'], ['access.global', 'modify.global'], true)) {
                    continue;
                }

                $key = sprintf('%s:%d:%s', basename($file), $message['line'], $message['identifier']);
                $diagnostics[$key] = ($diagnostics[$key] ?? 0) + 1;
            }
        }

        ksort($diagnostics);

        return $diagnostics;
    }
}
