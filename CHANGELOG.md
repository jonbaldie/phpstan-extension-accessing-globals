# Changelog

All notable changes to this project are documented in this file.

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