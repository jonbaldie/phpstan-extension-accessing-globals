# Changelog

All notable changes to this project are documented in this file.

## 0.5.0

- Detect mutations through by-reference builtins (#25, #30)
- Detect by-reference foreach mutation of superglobals (#24, #29)
- Detect dynamic `constant()` lookups in `ForbidUsingGlobalConstants` (#23, #28)
- Fix object-property mutations on global bindings (#22, #27)
- Resolve impure function calls before checking (#21, #26)
- Detect dynamic global declarations (#16, #20)
- Detect dimension writes to global variables (#15, #19)
- Detect `unset()` as a global mutation (#14, #18)
- Fix `foreach` mutation target detection (#13, #17)

## 0.4.0

- Treat root-scope closures as nested scopes (#5, #11)
- Fix `modify.global` nested shadowing and by-ref captures (#10)
- Fix compound global modifications (#9)
- Fix duplicate `modify.global` error in closures declaring their own globals (#8)
- Skip dynamic class operands in `ForbidUsingStaticPropertiesRule` (#7)

## 0.3.1

- CI workflow fixes

## 0.3.0

- CI workflow

## 0.2.6

- Clearer error wording

## 0.2.4

- Purposeful-failure test fixtures fixed

## 0.2.2

- Purposeful-failure test fixtures added

## 0.2.1

- Added PHP 8.4 to CI

## 0.2.0

- CI pinned to PHP 8.3

## 0.1.0

- New default ruleset