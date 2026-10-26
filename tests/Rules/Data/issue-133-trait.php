<?php
trait Issue133Trait
{
    public function f(): void
    {
        global $db;
        $db = 1;
    }
}
