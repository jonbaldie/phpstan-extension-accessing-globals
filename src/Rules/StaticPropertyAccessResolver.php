<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;

/**
 * Names the class and property a static property fetch reads. A named class
 * (including `self`, `static`, and `parent`) is kept as written; any other
 * class expression, `$this` included, resolves through its type when that is
 * exactly one class.
 */
final class StaticPropertyAccessResolver
{
    /**
     * Null when the class operand is not one known class. A property name
     * that is not one known string is reported as `{expression}`.
     */
    public function resolve(StaticPropertyFetch $node, Scope $scope): ?StaticPropertyAccess
    {
        $className = ClassOperandResolver::resolveName($node->class, $scope);
        if ($className === null) {
            return null;
        }

        return new StaticPropertyAccess($className, $this->resolvePropertyName($node->name, $scope));
    }

    private function resolvePropertyName(VarLikeIdentifier|Expr $name, Scope $scope): string
    {
        if ($name instanceof VarLikeIdentifier) {
            return $name->toString();
        }

        return ConstantStringResolver::resolve($name, $scope) ?? '{expression}';
    }
}
