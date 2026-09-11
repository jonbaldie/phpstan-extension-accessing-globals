<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue35;

class Config
{
    public const TIMEOUT = 30;
}

class BaseClass
{
    public const BASE_TIMEOUT = 10;
}

class Client extends BaseClass
{
    public const OWN_TIMEOUT = 20;

    public function process(): int
    {
        // OK: Accessing own constant via constant('self::...')
        $a = constant('self::OWN_TIMEOUT');

        // OK: Accessing own constant via constant('Client::...')
        $b = constant('Client::OWN_TIMEOUT');

        // OK: Accessing own constant via fully-qualified name
        $c = constant('AccessingGlobals\Tests\Rules\Data\Issue35\Client::OWN_TIMEOUT');

        // OK: Accessing parent constant via constant('parent::...')
        $d = constant('parent::BASE_TIMEOUT');

        // OK: Accessing static constant via constant('static::...')
        $e = constant('static::OWN_TIMEOUT');

        // BAD: Accessing external class constant inside method
        $f = constant('Config::TIMEOUT');

        // BAD: Accessing external class constant via fully qualified name inside method
        $g = constant('AccessingGlobals\Tests\Rules\Data\Issue35\Config::TIMEOUT');

        return $a + $b + $c + $d + $e + $f + $g;
    }
}

function doSomething(): int
{
    // BAD: Accessing external class constant inside function
    $timeout = constant('Config::TIMEOUT');

    // BAD: Accessing external class constant via fully qualified name inside function
    $fqTimeout = constant('AccessingGlobals\Tests\Rules\Data\Issue35\Config::TIMEOUT');

    // OK: Plain global constant should not be flagged by class constants rule
    $globalVal = constant('PHP_VERSION');

    return $timeout + $fqTimeout;
}

// OK: Accessing external class constant in root scope is not flagged
$rootTimeout = constant('Config::TIMEOUT');
