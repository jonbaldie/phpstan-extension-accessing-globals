<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Config;

use PHPUnit\Framework\TestCase;

class ScopeClassifierCompatibilityTest extends TestCase
{
    public function testClassDeclarationExpressionsRemainAllowed(): void
    {
        $root = dirname(__DIR__, 2);
        $command = [PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse',
            '-c', $root . '/config/rules-opinionated.neon',
            $root . '/tests/Rules/Data/ScopeClassifier/class-declarations.php',
            '--level=0', '--no-progress', '--error-format=json'];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        self::assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        self::assertSame(0, $exit, $output . $errors);
        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $result['totals']['file_errors']);
    }
}
