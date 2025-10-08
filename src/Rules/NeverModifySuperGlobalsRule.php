<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Assign>
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
        return Node\Expr\Assign::class;
    }

    /**
     * @param Node\Expr\Assign $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $var = $node->var;

        while ($var instanceof Node\Expr\ArrayDimFetch) {
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
                        'Code is modifying superglobal variable $%s. Use a wrapper service instead.',
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
