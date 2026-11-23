<?php
const EXTERNAL_CONST = 1;
class X { const V = 1; }
#[Attribute] class A { public function __construct($x) {} }
#[A(EXTERNAL_CONST)]
class C {
    #[A(EXTERNAL_CONST)] public $x = EXTERNAL_CONST;
    #[A(X::V)] public const Y = X::V;
    public function foo(#[A(EXTERNAL_CONST)] $x = EXTERNAL_CONST) {}
}
