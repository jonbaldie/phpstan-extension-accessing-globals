<?php

declare(strict_types=1);

namespace {
    class Config
    {
        public const TIMEOUT = 60;

        public function getOwnTimeout(): int
        {
            // OK: Accessing own constant in global namespace via constant('Config::...')
            return constant('Config::TIMEOUT');
        }
    }
}

namespace App\Services {
    class Config
    {
        public const LOCAL_TIMEOUT = 10;

        public function getExternalTimeout(): int
        {
            // BAD: Accessing external global class constant Config::TIMEOUT from namespaced class
            return constant('Config::TIMEOUT');
        }

        public function getOwnTimeouts(): int
        {
            // OK: Accessing own constant via fully-qualified class name
            $a = constant('App\Services\Config::LOCAL_TIMEOUT');

            // OK: Accessing own constant via fully-qualified class name with leading backslash
            $b = constant('\App\Services\Config::LOCAL_TIMEOUT');

            // OK: Accessing own constant via self keyword
            $c = constant('self::LOCAL_TIMEOUT');

            // OK: Accessing own constant via static keyword
            $d = constant('static::LOCAL_TIMEOUT');

            return $a + $b + $c + $d;
        }
    }
}
