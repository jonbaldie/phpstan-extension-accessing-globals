<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * Common PHP functions that are "impure" because they depend on or change
 * external state (e.g., system clock, environment, filesystem).
 */
final class ImpureFunctionCatalog
{
    private const FUNCTIONS = [
        // Time related
        'time' => true,
        'microtime' => true,
        'date' => true,
        'gmdate' => true,
        'getdate' => true,
        'hrtime' => true,
        'gettimeofday' => true,
        'strtotime' => true,
        'mktime' => true,
        'gmmktime' => true,
        'date_sunrise' => true,
        'date_sunset' => true,
        'date_default_timezone_get' => true,
        'date_default_timezone_set' => true,

        // Randomness related
        'rand' => true,
        'mt_rand' => true,
        'random_int' => true,
        'random_bytes' => true,
        'uniqid' => true,
        'shuffle' => true,
        'array_rand' => true,
        'str_shuffle' => true,

        // Environment related
        'getenv' => true,
        'putenv' => true,
        'apache_getenv' => true,
        'getallheaders' => true,
        'php_uname' => true,
        'php_sapi_name' => true,
        'phpversion' => true,
        'php_ini_loaded_file' => true,
        'php_ini_scanned_files' => true,
        'sys_getloadavg' => true,
        'getrusage' => true,
        'getcwd' => true,
        'gethostname' => true,
        'getmypid' => true,
        'getmyuid' => true,
        'getmygid' => true,
        'getmyinode' => true,
        'get_current_user' => true,
        'disk_free_space' => true,
        'disk_total_space' => true,
        'error_get_last' => true,
        'error_reporting' => true,
        'ini_get' => true,
        'ini_get_all' => true,
        'ini_set' => true,
        'get_include_path' => true,
        'set_include_path' => true,
        'connection_status' => true,
        'connection_aborted' => true,
        'ignore_user_abort' => true,

        // Filesystem/Network related
        'file_get_contents' => true,
        'file_put_contents' => true,
        'fopen' => true,
        'fread' => true,
        'fwrite' => true,
        'fgets' => true,
        'fgetc' => true,
        'fgetcsv' => true,
        'flock' => true,
        'readfile' => true,
        'move_uploaded_file' => true,
        'glob' => true,
        'scandir' => true,
        'opendir' => true,
        'readdir' => true,
        'stat' => true,
        'lstat' => true,
        'realpath' => true,
        'file' => true,
        'file_exists' => true,
        'fileatime' => true,
        'filectime' => true,
        'fileinode' => true,
        'filemtime' => true,
        'fileowner' => true,
        'filegroup' => true,
        'fileperms' => true,
        'filesize' => true,
        'filetype' => true,
        'is_dir' => true,
        'is_executable' => true,
        'is_file' => true,
        'is_link' => true,
        'is_readable' => true,
        'is_writable' => true,
        'is_writeable' => true,
        'touch' => true,
        'unlink' => true,
        'mkdir' => true,
        'rmdir' => true,
        'chmod' => true,
        'chown' => true,
        'chgrp' => true,
        'copy' => true,
        'rename' => true,
        'fsockopen' => true,
        'pfsockopen' => true,
        'readline' => true,
        'tempnam' => true,
        'tmpfile' => true,
        'sys_get_temp_dir' => true,

        // Output/Header related
        'header' => true,
        'setcookie' => true,
        'error_log' => true,
        'mail' => true,
        'syslog' => true,
        'setlocale' => true,
        'localeconv' => true,
        'session_start' => true,
        'session_id' => true,
        'session_status' => true,
        'session_name' => true,
        'session_regenerate_id' => true,
        'session_destroy' => true,
        'session_unset' => true,
        'register_shutdown_function' => true,
        'register_tick_function' => true,

        // Process execution
        'exec' => true,
        'shell_exec' => true,
        'passthru' => true,
        'system' => true,
        'proc_open' => true,
    ];

    public static function isImpure(string $functionName): bool
    {
        return isset(self::FUNCTIONS[strtolower($functionName)]);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::FUNCTIONS);
    }
}
