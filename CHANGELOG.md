# Changelog

All notable changes to this project are documented in this file.

## 0.6.0

- Detect `shuffle`, `array_rand`, `str_shuffle`, `tempnam`, `tmpfile`, `sys_get_temp_dir` as impure (#63, #65)
- Detect by-reference constructor arguments mutating globals (#62, #64)
- Report same-category impure functions missed by `ForbidImpureGlobalFunctionsRule` (#57, #61)
- Fix false positive for root-scope global declarations
- Detect `array_multisort` in-place mutation of globals (#55, #59)
- Resolve dynamic global declarations before mutations (#58)
- Detect global object mutations in arrow functions and by-value closures (#47, #53)
- Don't flag enum cases as forbidden class constants in `ForbidUsingClassConstantsRule` (#46, #52)
- Detect first-class callables of impure functions (#45, #51)
- Forbid short-name `constant()` lookups in namespaced classes (#44, #50)
- Detect mutations through method and static calls with by-reference parameters (#43, #49)
- Detect object property mutations on globals (#48)
- Fix literal dynamic superglobal detection (#36)
- Forbid class constant lookups via `constant()` (#35, #40)
- Register `NeverModifyGloballyDeclaredVariablesRule` in strict ruleset (#34, #39)
- Treat foreach key variables as mutation targets (#33, #38)
- Skip dynamic class constant fetches in `ForbidUsingClassConstantsRule` (#37)

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