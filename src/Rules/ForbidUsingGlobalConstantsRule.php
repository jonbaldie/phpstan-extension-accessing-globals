<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\ConstFetch>
 */
class ForbidUsingGlobalConstantsRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\ConstFetch::class;
    }

    /**
     * @param Node\Expr\ConstFetch $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // We only care about code inside a function or method
        if ($scope->getFunction() === null) {
            return [];
        }

        $constantName = $node->name->toString();
        $lowerCaseConstantName = strtolower($constantName);

        // Ignore PHP's built-in pseudo-constants
        if (in_array($lowerCaseConstantName, ['true', 'false', 'null'], true)) {
            return [];
        }

        // If we're here, it's a user-defined global constant.
        // This is a hidden dependency and should be passed as an argument instead.
        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Code is accessing global constant "%s". Pass it as an argument instead to make the dependency explicit.',
                    $constantName
                )
            )
                ->identifier('constant.global')
                ->build(),
        ];
    }
}
