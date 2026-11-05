<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\ArrayDimFetch;

/**
 * @implements Rule<Node>
 */
class NeverModifyGlobalsRule implements Rule
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
        $errors = [];

        foreach ($this->mutationTargetResolver->resolveTargets($node, $scope) as $target) {
            if (!$target->innermostFetch instanceof ArrayDimFetch || $target->rootName !== "GLOBALS") {
                continue;
            }

            $key = GlobalVariableNameResolver::resolveGlobalsKey($target->innermostFetch, $scope);
            $message = $key === null
                ? 'Code is modifying a global variable through $GLOBALS with a dynamic key. Use dependency injection instead.'
                : sprintf(
                    'Code is modifying global variable through $GLOBALS[\'%s\']. Use dependency injection instead.',
                    $key,
                );

            $errors[] = RuleErrorBuilder::message($message)
                ->identifier("modify.global")
                ->build();
        }

        return $errors;
    }
}
