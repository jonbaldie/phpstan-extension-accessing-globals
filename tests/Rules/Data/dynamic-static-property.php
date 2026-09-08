<?php

function readDynamicStaticProperty($object)
{
    return $object::$value;
}
