<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

class NeverAccessGlobalsRule extends AllowSuperGlobalsInRootScopeRule
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
        // A global declaration in file scope has no enclosing scope to import into.
        if ($this->isInRootScope($scope)) {
            return [];
        }

        $errors = [];
        foreach ($node->vars as $var) {
            if (!$var instanceof Node\Expr\Variable) {
                continue;
            }

            $name = GlobalVariableNameResolver::resolve($var, $scope);
            if ($name === null) {
                $errors[] = RuleErrorBuilder::message(
                    'Code is accessing a global variable with a dynamic name. Use dependency injection instead.',
                )
                    ->identifier("access.global")
                    ->build();
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing global variable $%s. Use dependency injection instead.',
                    $name,
                ),
            )
                ->identifier("access.global")
                ->build();
        }

        return $errors;
    }
}
