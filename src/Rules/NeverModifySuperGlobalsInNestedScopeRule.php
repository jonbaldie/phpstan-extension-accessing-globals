<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr>
 */
class NeverModifySuperGlobalsInNestedScopeRule extends
    AllowSuperGlobalsInRootScopeRule
{
    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param Node\Expr $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInRootScope($scope)) {
            return [];
        }

        $errors = [];
        foreach (MutationTargetResolver::resolve($node) as $target) {
            $var = $target;

            while ($var instanceof Node\Expr\ArrayDimFetch) {
                $var = $var->var;
            }

            if (!$var instanceof Variable || !is_string($var->name)) {
                continue;
            }

            if (in_array($var->name, $this->superglobals, true)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Code is modifying superglobal variable $%s in a nested scope. Return the new value instead.',
                        $var->name,
                    ),
                )
                    ->identifier("modify.superglobal.nested")
                    ->build();
            }
        }

        return $errors;
    }
}
