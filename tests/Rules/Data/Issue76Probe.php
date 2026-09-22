<?php

namespace AccessingGlobals\Tests\Rules\Data;

class Issue76Probe
{
    public static $prop = 'value';

    public function readThis()
    {
        return $this::$prop;
    }
}
