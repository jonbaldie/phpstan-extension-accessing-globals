<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;

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

        foreach ($this->mutationTargetResolver->resolve($node, $scope) as $target) {
            if (
                !$target instanceof ArrayDimFetch
                && !$target instanceof Node\Expr\PropertyFetch
            ) {
                continue;
            }

            $globalTarget = $target;
            while (
                $globalTarget->var instanceof ArrayDimFetch
                || $globalTarget->var instanceof Node\Expr\PropertyFetch
            ) {
                $globalTarget = $globalTarget->var;
            }

            if (
                !$globalTarget instanceof ArrayDimFetch
                || !$globalTarget->var instanceof Variable
            ) {
                continue;
            }

            $name = GlobalVariableNameResolver::resolve($globalTarget->var, $scope);
            if ($name !== "GLOBALS") {
                continue;
            }

            $key = GlobalVariableNameResolver::resolveGlobalsKey($globalTarget, $scope);
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
