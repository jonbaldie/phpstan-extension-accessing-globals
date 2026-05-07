<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class NeverModifySuperGlobalsRule implements Rule
{
    /**
     * @var string[]
     */
    private array $superglobals = [
        "_GET",
        "_POST",
        "_REQUEST",
        "_SESSION",
        "_COOKIE",
        "_FILES",
        "_ENV",
        "_SERVER",
        "GLOBALS",
    ];

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $var = match (true) {
            $node instanceof Node\Expr\Assign,
            $node instanceof AssignOp,
            $node instanceof PreInc,
            $node instanceof PreDec,
            $node instanceof PostInc,
            $node instanceof PostDec => $node->var,
            default => null,
        };

        if ($var === null) {
            return [];
        }

        while ($var instanceof ArrayDimFetch) {
            $var = $var->var;
        }

        if (!$var instanceof Variable) {
            return [];
        }

        if (!is_string($var->name)) {
            return [];
        }

        if (in_array($var->name, $this->superglobals, true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is modifying superglobal variable $%s. Return the new value instead.',
                        $var->name,
                    ),
                )
                    ->identifier("modify.superglobal")
                    ->build(),
            ];
        }

        return [];
    }
}
