<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

final class GlobalVariableNameResolver
{
    public static function resolve(Node\Expr\Variable $variable, Scope $scope): ?string
    {
        if (is_string($variable->name)) {
            return $variable->name;
        }

        $constantStrings = $scope->getType($variable->name)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}
