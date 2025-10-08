<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class ForbidImpureGlobalFunctionsRule implements Rule
{
    /**
     * @var array<string, bool>
     */
    private array $impureFunctions;

    public function __construct()
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

            // Randomness related
            'rand',
            'mt_rand',
            'random_int',
            'random_bytes',

            // Environment related
            'getenv',
            'apache_getenv',
            'getallheaders',
            'php_uname',
            'sys_getloadavg',
            'uniqid',

            // Filesystem/Network related
            'file_get_contents',
            'file_put_contents',
            'fopen',
            'fread',
            'fwrite',
            'readfile',
            'move_uploaded_file',

            // Output/Header related
            'header',
            'setcookie',
            'session_start',
            'session_id',

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
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method.
        if ($scope->getFunction() === null) {
            return [];
        }

        if (!$node->name instanceof Name) {
            // This handles dynamic function calls like `$functionName()`.
            // These are a separate problem and not the focus of this rule.
            return [];
        }

        $functionName = $node->name->toLowerString();

        if (isset($this->impureFunctions[$functionName])) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is calling the impure function "%s()". This creates a hidden dependency on external state; pass the result as an argument instead.',
                        $node->name->toString()
                    )
                )
                    ->identifier('function.impure')
                    ->build(),
            ];
        }

        return [];
    }
}
