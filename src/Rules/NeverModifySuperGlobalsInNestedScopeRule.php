<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

class NeverModifySuperGlobalsInNestedScopeRule extends
    AllowSuperGlobalsInRootScopeRule
{
    public function __construct(
        private readonly MutationTargetResolver $mutationTargetResolver,
    ) {
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param Node $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInRootScope($scope)) {
            return [];
        }

        $errors = [];
        foreach ($this->mutationTargetResolver->resolve($node, $scope) as $target) {
            $var = $target;

            while (
                $var instanceof Node\Expr\ArrayDimFetch
                || $var instanceof Node\Expr\PropertyFetch
            ) {
                $var = $var->var;
            }

            if (!$var instanceof Variable) {
                continue;
            }

            $name = GlobalVariableNameResolver::resolve($var, $scope);
            if ($name === null) {
                continue;
            }

            if (in_array($name, $this->superglobals, true)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Code is modifying superglobal variable $%s in a nested scope. Return the new value instead.',
                        $name,
                    ),
                )
                    ->identifier("modify.superglobal.nested")
                    ->build();
            }
        }

        return $errors;
    }
}
