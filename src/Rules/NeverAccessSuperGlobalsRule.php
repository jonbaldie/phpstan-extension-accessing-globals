<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Variable>
 */
class NeverAccessSuperGlobalsRule implements Rule
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
        return Node\Expr\Variable::class;
    }

    /**
     * @param Node\Expr\Variable $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!is_string($node->name)) {
            return [];
        }

        if (in_array($node->name, $this->superglobals, true)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Code is accessing superglobal variable $%s. Pass the value as an argument instead.',
                        $node->name,
                    ),
                )
                    ->identifier("access.superglobal")
                    ->build(),
            ];
        }

        return [];
    }
}
