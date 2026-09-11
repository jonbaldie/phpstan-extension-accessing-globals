<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Runs the real PHPStan binary against the shipped config files, so that
 * missing rule registrations in a ruleset are caught.
 */
class StrictRulesetTest extends TestCase
{
    public function testStrictRulesetReportsWritesToGlobalDeclaredVariables(): void
    {
        $errors = $this->analyse(
            'config/rules-strict.neon',
            __DIR__ . '/../Rules/Data/issue-34-strict-global-declared-mutation.php',
        );

        $this->assertContains(
            '9:modify.global',
            $errors,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function mutationFixtures(): iterable
    {
        foreach ([
            'modify-globals.php',
            'modify-globals-in-closure.php',
            'modify-superglobals-in-nested-scope.php',
            'issue-3-compound-modifications.php',
            'issue-13-foreach-global-targets.php',
            'issue-14-unset.php',
            'issue-15-dimension-writes.php',
            'issue-25-global-keyword-by-reference.php',
            'issue-25-globals-by-reference.php',
            'issue-33-foreach-key-targets.php',
            'issue-34-strict-global-declared-mutation.php',
        ] as $fixture) {
            yield $fixture => [$fixture];
        }
    }

    /**
     * Every line the default ruleset flags as a mutation must also be flagged
     * as a mutation by the strict ruleset.
     */
    #[DataProvider('mutationFixtures')]
    public function testStrictRulesetIsSupersetOfDefaultForMutations(string $fixture): void
    {
        $path = __DIR__ . '/../Rules/Data/' . $fixture;

        $defaultLines = $this->mutationLines($this->analyse('config/rules.neon', $path));
        $strictLines = $this->mutationLines($this->analyse('config/rules-strict.neon', $path));

        $this->assertNotEmpty($defaultLines);
        $this->assertSame([], array_values(array_diff($defaultLines, $strictLines)));
    }

    /**
     * @param list<string> $errors
     * @return list<string>
     */
    private function mutationLines(array $errors): array
    {
        $lines = [];

        foreach ($errors as $error) {
            [$line, $identifier] = explode(':', $error, 2);

            if (str_starts_with($identifier, 'modify.')) {
                $lines[] = $line;
            }
        }

        return array_values(array_unique($lines));
    }

    /**
     * @return list<string> errors formatted as "line:identifier"
     */
    private function analyse(string $config, string $path): array
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
            $path,
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

        $errors = [];

        foreach ($result['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $errors[] = $message['line'] . ':' . $message['identifier'];
            }
        }

        return $errors;
    }
}
