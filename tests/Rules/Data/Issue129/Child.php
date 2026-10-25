<?php

namespace AccessingGlobals\Tests\Rules\Data\Issue129;

class Child extends Client
{
    public function inherited(): int
    {
        return parent::OWN;
    }
}
