<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr>
 */
class ForbidImpureGlobalFunctionsRule implements Rule
{
    /**
     * @var array<string, bool>
     */
    private array $impureFunctions;

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    )
    {
        // A list of common PHP functions that are "impure" because they
        // depend on external state (e.g., system clock, environment, filesystem).
        $functions = [
            // Time related
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

            // Randomness related
            'rand',
            'mt_rand',
            'random_int',
            'random_bytes',
            'uniqid',

            // Environment related
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
            'getcurrentuser',
            'disk_free_space',
            'disk_total_space',
            'error_get_last',
            'error_reporting',
            'ini_get',
            'ini_get_all',
            'ini_set',
            'set_include_path',
            'connection_status',
            'connection_aborted',
            'ignore_user_abort',

            // Filesystem/Network related
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

            // Output/Header related
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

            // Process execution
            'exec',
            'shell_exec',
            'passthru',
            'system',
            'proc_open',
        ];

        $this->impureFunctions = array_flip($functions);
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method.
        // Closures and arrow functions defined at file scope have no enclosing
        // function, but their bodies are nested scopes, not root code.
        if ($scope->getFunction() === null && !$scope->isInAnonymousFunction()) {
            return [];
        }

        if ($node instanceof FunctionCallableNode) {
            $name = $node->getName();
        } elseif ($node instanceof FuncCall) {
            $name = $node->name;
        } else {
            return [];
        }

        if (!$name instanceof Name) {
            // This handles dynamic function calls like `$functionName()`.
            // These are a separate problem and not the focus of this rule.
            return [];
        }

        $resolvedFunctionName = $this->reflectionProvider->resolveFunctionName($name, $scope);

        if ($resolvedFunctionName === null) {
            return [];
        }

        $functionName = strtolower($resolvedFunctionName);

        if (isset($this->impureFunctions[$functionName])) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is calling the impure function "%s()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                        $name->toString()
                    )
                )
                    ->identifier('function.impure')
                    ->build(),
            ];
        }

        return [];
    }
}
