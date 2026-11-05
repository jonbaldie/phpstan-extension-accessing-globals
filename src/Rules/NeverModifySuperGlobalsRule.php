<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class NeverModifySuperGlobalsRule implements Rule
{
    public function __construct(
        private readonly MutationTargetResolver $mutationTargetResolver,
    ) {
    }

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
        return Node::class;
    }

    /**
     * @param Node $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($this->mutationTargetResolver->resolveTargets($node, $scope) as $target) {
            $name = $target->rootName;
            if ($name === null) {
                continue;
            }

            if (in_array($name, $this->superglobals, true)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Code is modifying superglobal variable $%s. Return the new value instead.',
                        $name,
                    ),
                )
                    ->identifier("modify.superglobal")
                    ->build();
            }
        }

        return $errors;
    }
}
