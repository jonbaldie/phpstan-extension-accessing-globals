<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VoidType;

/**
 * @implements Rule<MethodCall>
 */
class EnforceImmutableObjectUpdatesRule implements Rule
{
    /**
     * @var array<string, bool>
     */
    private array $mutatorPrefixes;

    public function __construct()
    {
        $prefixes = [
            'set',
            'add',
            'update',
            'remove',
            'delete',
            'modify',
            'change',
            'push',
            'pop',
            'clear',
            'reset',
        ];
        $this->mutatorPrefixes = array_flip($prefixes);
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $function = $scope->getFunction();
        if ($function === null) {
            return [];
        }

        // This rule only triggers if the method call's return value is ignored.
        // This is a strong signal of a "fire-and-forget" side effect.
        $parent = $node->getAttribute('parent');
        if (!$parent instanceof Expression) {
            return [];
        }

        if (!$node->var instanceof Variable || !is_string($node->var->name)) {
            return [];
        }

        $variableName = $node->var->name;
        $isParameter = false;
        foreach ($function->getParameters() as $parameter) {
            if ($parameter->getName() === $variableName) {
                $isParameter = true;
                break;
            }
        }

        if (!$isParameter) {
            return [];
        }

        $paramType = $scope->getType($node->var);
        if (!$paramType instanceof ObjectType) {
            return [];
        }

        $methodName = $node->name->toString();
        $methodReflection = $scope->getMethodReflection($paramType, $methodName);

        // Heuristic 1: If the method returns void, it's definitely a mutator.
        if ($methodReflection !== null) {
            $returnType = $methodReflection->getVariants()[0]->getReturnType();
            if ($returnType instanceof VoidType) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'Method "%s()" on parameter $%s has a void return type, indicating it mutates state. Functions should return a new state instead of modifying arguments.',
                            $methodName,
                            $variableName
                        )
                    )
                        ->identifier('object.mutation.void')
                        ->build(),
                ];
            }
        }

        // Heuristic 2: The method name suggests mutation.
        foreach ($this->mutatorPrefixes as $prefix => $_) {
            if (str_starts_with($methodName, $prefix)) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            'Method "%s()" on parameter $%s appears to be a mutator. Functions should return a new state instead of modifying arguments.',
                            $methodName,
                            $variableName
                        )
                    )
                        ->identifier('object.mutation.name')
                        ->build(),
                ];
            }
        }

        // Default case: An ignored return value from a method on a parameter.
        return [
            RuleErrorBuilder::message(
                sprintf(
                    'The return value of "%s()" on parameter $%s was ignored. This suggests a side-effect (mutation). To ensure predictable data flow, return a new object with the updated state.',
                    $methodName,
                    $variableName
                )
            )
                ->identifier('object.mutation.ignored-return')
                ->build(),
        ];
    }
}
