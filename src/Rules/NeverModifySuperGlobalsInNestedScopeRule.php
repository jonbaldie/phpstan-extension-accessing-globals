<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
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
        foreach ($this->mutationTargetResolver->resolveTargets($node, $scope) as $target) {
            $name = $target->rootName;
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
