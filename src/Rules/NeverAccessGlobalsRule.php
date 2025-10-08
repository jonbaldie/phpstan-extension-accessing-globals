<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\Global_>
 */
class NeverAccessGlobalsRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Stmt\Global_::class;
    }

    /**
     * @param Node\Stmt\Global_ $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($node->vars as $var) {
            if (!$var instanceof Node\Expr\Variable || !is_string($var->name)) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing global variable $%s. Use dependency injection instead.',
                    $var->name,
                ),
            )
                ->identifier("access.global")
                ->build();
        }

        return $errors;
    }
}
