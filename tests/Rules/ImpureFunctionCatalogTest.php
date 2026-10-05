<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\ImpureFunctionCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImpureFunctionCatalogTest extends TestCase
{
    public function testKnownImpureFunctionIsImpure(): void
    {
        self::assertTrue(ImpureFunctionCatalog::isImpure('time'));
    }

    #[DataProvider('pureOrUnknownFunctionNames')]
    public function testPureOrUnknownFunctionIsNotImpure(string $functionName): void
    {
        self::assertFalse(ImpureFunctionCatalog::isImpure($functionName));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pureOrUnknownFunctionNames(): iterable
    {
        yield 'pure global function' => ['strlen'];
        yield 'unknown function' => ['not_a_real_function'];
        yield 'empty string' => [''];
        yield 'namespaced impure name' => ['App\\time'];
        yield 'misspelled get_current_user' => ['getcurrentuser'];
    }

    #[DataProvider('mixedCaseImpureFunctionNames')]
    public function testLookupIsCaseInsensitive(string $functionName): void
    {
        self::assertTrue(ImpureFunctionCatalog::isImpure($functionName));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function mixedCaseImpureFunctionNames(): iterable
    {
        yield 'upper case' => ['TIME'];
        yield 'mixed case' => ['File_Get_Contents'];
        yield 'title case' => ['Getenv'];
    }

    public function testCatalogContainsExactlyTheFunctionsPreviouslyOwnedByTheRule(): void
    {
        $expected = [
            'time',
            'microtime',
            'date',
            'gmdate',
            'getdate',
            'hrtime',
            'gettimeofday',
            'strtotime',
            'mktime',
            'gmmktime',
            'date_sunrise',
            'date_sunset',
            'date_default_timezone_get',
            'date_default_timezone_set',
            'rand',
            'mt_rand',
            'random_int',
            'random_bytes',
            'uniqid',
            'shuffle',
            'array_rand',
            'str_shuffle',
            'getenv',
            'putenv',
            'apache_getenv',
            'getallheaders',
            'php_uname',
            'php_sapi_name',
            'phpversion',
            'php_ini_loaded_file',
            'php_ini_scanned_files',
            'sys_getloadavg',
            'getrusage',
            'getcwd',
            'gethostname',
            'getmypid',
            'getmyuid',
            'getmygid',
            'getmyinode',
            'get_current_user',
            'disk_free_space',
            'disk_total_space',
            'error_get_last',
            'error_reporting',
            'ini_get',
            'ini_get_all',
            'ini_set',
            'get_include_path',
            'set_include_path',
            'connection_status',
            'connection_aborted',
            'ignore_user_abort',
            'file_get_contents',
            'file_put_contents',
            'fopen',
            'fread',
            'fwrite',
            'fgets',
            'fgetc',
            'fgetcsv',
            'flock',
            'readfile',
            'move_uploaded_file',
            'glob',
            'scandir',
            'opendir',
            'readdir',
            'stat',
            'lstat',
            'realpath',
            'file',
            'file_exists',
            'fileatime',
            'filectime',
            'fileinode',
            'filemtime',
            'fileowner',
            'filegroup',
            'fileperms',
            'filesize',
            'filetype',
            'is_dir',
            'is_executable',
            'is_file',
            'is_link',
            'is_readable',
            'is_writable',
            'is_writeable',
            'touch',
            'unlink',
            'mkdir',
            'rmdir',
            'chmod',
            'chown',
            'chgrp',
            'copy',
            'rename',
            'fsockopen',
            'pfsockopen',
            'readline',
            'tempnam',
            'tmpfile',
            'sys_get_temp_dir',
            'header',
            'setcookie',
            'error_log',
            'mail',
            'syslog',
            'setlocale',
            'localeconv',
            'session_start',
            'session_id',
            'session_status',
            'session_name',
            'session_regenerate_id',
            'session_destroy',
            'session_unset',
            'register_shutdown_function',
            'register_tick_function',
            'exec',
            'shell_exec',
            'passthru',
            'system',
            'proc_open',
        ];

        $actual = ImpureFunctionCatalog::all();
        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }
}
