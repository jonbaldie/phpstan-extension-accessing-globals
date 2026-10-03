<?php

declare(strict_types=1);

namespace AccessingGlobals\ExploratoryTesting;

class HookExample
{
    public string $name {
        get {
            return (string) time();
        }
        set {
            global $db;
            $db = $value;
        }
    }
}
