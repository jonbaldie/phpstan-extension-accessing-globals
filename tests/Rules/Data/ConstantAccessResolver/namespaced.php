<?php

declare(strict_types=1);

namespace ConstantAccessResolver\Shadowed {
    function constant(string $name): string
    {
        return $name;
    }

    function readsShadowedConstant(): string
    {
        return constant('FOO');
    }
}

namespace ConstantAccessResolver\Unshadowed {
    function readsBuiltinConstant(): mixed
    {
        return constant('FOO');
    }
}
