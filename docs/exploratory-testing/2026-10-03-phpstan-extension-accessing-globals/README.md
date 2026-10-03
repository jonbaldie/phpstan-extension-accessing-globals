# Exploratory testing: phpstan-extension-accessing-globals

Date: 2026-10-03

## Scope and setup

I drove the extension through its public PHPStan CLI using the repository's three published rule configurations (`config/rules.neon`, `config/rules-strict.neon`, `config/rules-opinionated.neon`) against the standard test fixtures, edge cases, and modern PHP language constructs. The checkout started clean at `origin/main` commit `f09998d`.

- PHP 8.5.9
- Composer 2.10.2
- PHPStan 2.2.13
- PHPUnit 12.5.34
- Evidence directory: [`evidence/`](evidence/)
- `vendor/bin/phpunit`: 123 tests and 260 assertions passed; output is in [`phpunit.txt`](evidence/phpunit.txt).

PHPStan's trailing whitespace used for terminal table alignment was trimmed; diagnostic messages, identifiers, line numbers, counts, and exit codes are fully preserved in the evidence logs.

## Journeys

### Journey 1: Default rules — Globals and nested superglobals with modern PHP features and callables

**Goal:** Verify detection of reading and modifying globally declared variables and superglobals in nested scopes, while permitting root-scope superglobals. Explore modern PHP language features (match expressions, nested destructuring, generators, property hooks) and invocation patterns (by-reference calls, method chains, and invokables).

The documented default aggregate command completed with exit code 1 and exactly 36 diagnostics, as expected: [`default-rules.txt`](evidence/default-rules.txt). The root-scope check reading `$_GET` and `$_SESSION` and assigning root variables completed with exit code 0 (`[OK] No errors`): [`default-root.txt`](evidence/default-root.txt).

I then tested modern syntax variations:
- **Match expressions and destructuring:** Match arms containing reads and writes to globals and superglobals, as well as nested/keyed array destructuring (`[[$a], 'key' => $b] = ...`), correctly emit their expected diagnostics (`access.global`, `modify.global`, `access.superglobal.nested`, `modify.superglobal.nested`): [`match-and-destructuring-test.php`](evidence/match-and-destructuring-test.php) and [`match-and-destructuring-run.txt`](evidence/match-and-destructuring-run.txt).
- **PHP 8.4 property hooks:** Hook bodies (`get` and `set`) were confirmed to function as nested scopes, successfully catching impure function calls (`time()`) and global variable accesses and mutations: [`property-hooks-test.php`](evidence/property-hooks-test.php) and [`property-hooks-run.txt`](evidence/property-hooks-run.txt).
- **Surprise & Confirmed Bug (Invokable objects):** When an invokable object instance (`__invoke(mixed &$data)`) is called using natural PHP invokable syntax (`$mutator($appState)`, `$mutator($_SESSION)`, `$mutator($GLOBALS['config'])`), `ByRefArgumentResolver` bails out because `$call->name` is a `Node\Expr` rather than a `Node\Name`. Consequently, all four mutation rules (`NeverModifyGloballyDeclaredVariablesRule`, `NeverModifySuperGlobalsInNestedScopeRule`, `NeverModifySuperGlobalsRule`, `NeverModifyGlobalsRule`) silently fail to report the in-place modification. In contrast, calling the same instance explicitly via `$mutator->__invoke(...)` reports all modification errors. I verified that PHP mutates all three targets at runtime in [`invokable-runtime-verification.txt`](evidence/invokable-runtime-verification.txt), captured the missed diagnostics in [`invokable-mutation-run.txt`](evidence/invokable-mutation-run.txt), and filed [issue #104](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/104).

### Journey 2: Strict rules — Superglobals across all scopes

**Goal:** Reject all superglobal reads and writes at every scope, including root file scope.

The documented strict aggregate command completed with exit code 1 and exactly 28 diagnostics, as expected: [`strict-rules.txt`](evidence/strict-rules.txt). Running the strict configuration against root-scope fixtures (`superglobals-root-scope-read-write.php` and `access-superglobals-at-root-scope.php`) produced 12 diagnostics as expected: [`strict-root.txt`](evidence/strict-root.txt), while the same files produced 0 errors under default rules.

I also probed by-reference mutations at root scope: passing bare `$GLOBALS` by reference to a mutator (`$m->mutate($GLOBALS)`) at root scope reported both `access.superglobal` and `modify.superglobal` under strict rules with exit code 1, while completing with exit code 0 under default rules.

### Journey 3: Opinionated rules — Class constants, static properties, and impure functions

**Goal:** Detect external class constant accesses, static property fetches, and impure global function calls.

The combined opinionated verification command completed with exit code 1 and exactly 11 diagnostics as documented: [`opinionated-rules.txt`](evidence/opinionated-rules.txt).
- **Class constants & Enums:** Enum case references (`Status::Pending`) are correctly recognized as algebraic variants and allowed without error. References to constants on external classes (`Config::TIMEOUT`) are flagged with `constant.class`.
- **Impure functions:** Calling impure functions directly or referencing them as first-class callables (`time(...)`) is detected with `function.impure`.
- **Static properties:** Static property access via `self::$prop`, `static::$prop`, and `$this::$prop` is correctly reported as `property.static`.

## Findings and limits

- **Confirmed Bug (Issue #104):** `ByRefArgumentResolver` does not resolve parameters for invokable object calls (`$invokable($var)`). In-place mutations to globally declared variables, superglobals, and `$GLOBALS` entries via invokable callables go completely unreported across all modification rules. Replay fixture: [`invokable-mutation-reproducer.php`](evidence/invokable-mutation-reproducer.php); filed as [issue #104](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/104).
- **Usability Observation:** `ForbidUsingGlobalConstantsRule` flags PHP built-in constants (e.g. `PHP_EOL`, `JSON_THROW_ON_ERROR`, `DIRECTORY_SEPARATOR`) because only `true`, `false`, and `null` are excluded. Code comments state that any remaining constant is assumed to be user-defined.

The ordinary path and variations were exercised for all three rule sets. All unit tests remain green. No product source files were changed during this pass.
