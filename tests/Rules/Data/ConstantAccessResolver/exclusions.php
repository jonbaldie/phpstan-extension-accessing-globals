<?php

namespace ResolverExclusions;

use ResolverExclusions\External as Alias;

class External
{
    public const LIMIT = 10;
}
enum Priority
{
    case High;
    public const LIMIT = 5;
}
class Base
{
    public const LIMIT = 1;
}
class Client extends Base
{
    public const LIMIT = 10;

    /** @param class-string<External> $class */
    public function read(External $object, string $class, mixed $unknown, string $member): void
    {
        echo self::LIMIT, parent::LIMIT, static::LIMIT;
        echo Client::LIMIT, CLIENT::LIMIT, \ResolverExclusions\CLIENT::LIMIT;
        echo constant('self::LIMIT'), constant('parent::LIMIT'), constant('static::LIMIT');
        echo constant('ResolverExclusions\CLIENT::LIMIT');
        echo constant('\ResolverExclusions\Client::LIMIT');
        echo External::class, constant('ResolverExclusions\External::class');
        echo Priority::High, constant('ResolverExclusions\Priority::High');
        echo External::{$member}, $object::{$member};
        echo $unknown::LIMIT;
        echo constant('::LIMIT'), constant('External::');
        echo $object::LIMIT;
        echo $class::LIMIT;
        echo Alias::LIMIT;
        echo Priority::LIMIT;
        echo constant('ResolverExclusions\Priority::LIMIT');
    }
}
