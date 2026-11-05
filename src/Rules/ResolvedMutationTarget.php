<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;

final class ResolvedMutationTarget
{
    public function __construct(
        public readonly Expr $expression,
        public readonly Expr $root,
        public readonly ?string $rootName,
        public readonly ArrayDimFetch|PropertyFetch|null $innermostFetch,
        public readonly ArrayDimFetch|PropertyFetch|null $outermostFetch,
        public readonly bool $throughObjectHandle,
    ) {
    }
}
