<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules;

use AccessingGlobals\Rules\MutationTargetResolver;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPUnit\Framework\TestCase;

class ResolvedMutationTargetTest extends TestCase
{
    public function testKnownDynamicName(): void
    {
        $scope = $this->createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantStringType('_GET'));

        $target = MutationTargetResolver::describeTarget(new Variable(new Variable('name')), $scope);

        self::assertSame('_GET', $target->rootName);
    }

    public function testTargetShapes(): void
    {
        $scope = $this->createStub(Scope::class);
        $scope->method('getType')->willReturn(new MixedType());

        $a = new Variable('a');
        $array = new ArrayDimFetch($a, new Int_(0));
        $property = new PropertyFetch($a, 'p');
        $nested = new ArrayDimFetch(new PropertyFetch($array, 'p'), new Int_(1));
        $globals = new Variable('GLOBALS');
        $globalKey = new ArrayDimFetch($globals, new String_('x'));
        $globalNested = new ArrayDimFetch($globalKey, new Int_(0));
        $dynamic = new Variable(new Variable('name'));
        $call = new FuncCall(new Name('foo'));
        $callProperty = new PropertyFetch($call, 'p');

        $cases = [
            [$a, $a, 'a', null, null, false],
            [$array, $a, 'a', $array, $array, false],
            [$property, $a, 'a', $property, $property, true],
            [$nested, $a, 'a', $array, $nested, true],
            [$globalNested, $globals, 'GLOBALS', $globalKey, $globalNested, false],
            [$dynamic, $dynamic, null, null, null, false],
            [$callProperty, $call, null, $callProperty, $callProperty, true],
        ];

        foreach ($cases as [$expression, $root, $name, $inner, $outer, $throughObject]) {
            $target = MutationTargetResolver::describeTarget($expression, $scope);
            self::assertSame($expression, $target->expression);
            self::assertSame($root, $target->root);
            self::assertSame($name, $target->rootName);
            self::assertSame($inner, $target->innermostFetch);
            self::assertSame($outer, $target->outermostFetch);
            self::assertSame($throughObject, $target->throughObjectHandle);
        }
    }
}
