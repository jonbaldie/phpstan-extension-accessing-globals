<?php

namespace AccessingGlobals\Tests\Rules\Data;

define('ISSUE_TWENTY_THREE_CONST', 'production');

// Reading a global constant through constant() in the root scope is fine.
$a = constant('ISSUE_TWENTY_THREE_CONST');

// Reading a global constant through constant() inside a function is a hidden
// dependency, just like a plain constant fetch, and should be flagged.
function readsGlobalConstantDynamically(): string
{
    return constant('ISSUE_TWENTY_THREE_CONST');
}

// A dynamic constant name cannot be resolved statically; this should still be
// flagged conservatively.
function readsGlobalConstantWithDynamicName(string $name): string
{
    return constant($name);
}