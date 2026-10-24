# Exploratory testing: phpstan-extension-accessing-globals

Date: 2026-10-24

## Scope and setup

I drove the extension through its public interface, the PHPStan CLI with the three published rule configurations. I also tested it as a dependency of a separate consumer project installed through Composer and `phpstan/extension-installer`. Earlier passes ([2026-09-26](../2026-09-26-phpstan-extension-accessing-globals/README.md), [2026-10-03](../2026-10-03-phpstan-extension-accessing-globals/README.md)) covered the bundled fixtures and many single-rule edge cases. This pass focused on the consumer install journey, reference aliasing, root-scope block forms, and class operands in the opinionated rules.

- Checkout: `main` fast-forwarded to `5d64452` before testing
- PHP 8.5.9, Composer 2.10.2
- Repository: PHPStan 2.2.13 (lockfile), installed with `composer install` from `composer.lock` ([`setup.txt`](evidence/setup.txt))
- Consumer project: a scratch directory under the run's `$TMPDIR` that installs this checkout through a Composer `path` repository (mirrored, not symlinked). Composer resolved PHPStan 2.3.1 and extension-installer 1.4.3 there.
- `vendor/bin/phpunit`: 156 tests and 299 assertions passed ([`phpunit.txt`](evidence/phpunit.txt))
- Evidence directory: [`evidence/`](evidence/)
- No Docker containers, images or volumes were created.

## Journeys

### Journey 1: Install into a consumer project and adopt the rules

**Goal:** A user runs `composer require`, gets the default rules automatically through extension-installer, optionally switches to strict-only as the README describes, and records existing violations in a baseline.

- **Documented commands:** every manual verification command in `CLAUDE.md` matched its stated count. Default nested fixtures: 36, exit 1 ([`default-rules.txt`](evidence/default-rules.txt)). Default root fixtures: `[OK] No errors`, exit 0 ([`default-root.txt`](evidence/default-root.txt)). Strict root fixtures: 9 + 3 = 12 ([`strict-root.txt`](evidence/strict-root.txt)). Strict nested fixtures: 28 ([`strict-rules.txt`](evidence/strict-rules.txt)). Opinionated fixtures: 11 ([`opinionated-rules.txt`](evidence/opinionated-rules.txt)).
- **Ordinary path:** extension-installer registered `config/rules.neon` from `extra.phpstan.includes`. With no `includes` in the consumer's `phpstan.neon`, PHPStan reported `access.global`, `modify.global` and `access.superglobal.nested` in a class method, and allowed root-scope `$_GET` in `index.php` ([`consumer-installer-default.txt`](evidence/consumer-installer-default.txt)).
- **Variation, strict-only:** I set the installer `ignore` entry from the README, reinstalled and included `rules-strict.neon`. The run then reported only the strict identifiers, including root-scope `$_GET` ([`consumer-strict-only.txt`](evidence/consumer-strict-only.txt)). See the result-cache observation below.
- **Lasting effect, baseline:** `--generate-baseline` wrote 4 entries keyed by identifier. Re-analysing with the baseline included returned `[OK] No errors` with exit 0 ([`consumer-baseline.txt`](evidence/consumer-baseline.txt)).

### Journey 2: Default and strict rules against reference aliasing and root-scope block forms

**Goal:** Mutations of global state through references are reported, and code at file scope inside blocks is still treated as root scope.

- **Reference aliasing** ([`probe-mutation-aliases.php`](evidence/probe-mutation-aliases.php), [run](evidence/probe-mutation-aliases-run.txt)): `$alias = &$config`, `foreach ($items as &$item)`, `function () use (&$counter)`, `$s = &$_SESSION` and `$g = &$GLOBALS['config']` are all reported. Each is reported where the reference is bound. A function-local `static $n` is not reported, which is correct.
- **Root-scope blocks** ([`probe-root-scope-blocks.php`](evidence/probe-root-scope-blocks.php), [run](evidence/probe-root-scope-blocks-run.txt)): superglobal use at file scope inside a braced `namespace {}`, `if` or `try` is allowed by `rules.neon` and reported by `rules-strict.neon`. Bodies of an anonymous-class method and a conditionally declared function are treated as nested in both configurations.

### Journey 3: Opinionated rules with class operands and indirect calls

**Goal:** Constants of other classes, static properties and impure functions are reported however the code reaches them.

[`probe-opinionated.php`](evidence/probe-opinionated.php), [run](evidence/probe-opinionated-run.txt):

- `$config::$hits` (static property through an instance) and `\date()` in a namespace are reported.
- `$config::TIMEOUT` and `$cls::TIMEOUT` are **not** reported. See confirmed bug 1.
- `call_user_func('rand')` and `array_map('microtime', …)` are not reported. #115 explicitly put these out of scope, so I rejected them as bugs.
- Inside a class, `CLIENT::LIMIT` (the class's own name in different case) is reported as an external constant. See confirmed bug 2.

## Confirmed bugs

### 1. `$instance::CONST` and `$classString::CONST` bypass `ForbidUsingClassConstantsRule` (filed as [#129](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/129))

- **Impact:** a function can depend on another class's constant without a diagnostic just by reading it through an object or class-string. The sibling static-property rule reports the same operand form.
- **Starting state:** this checkout at `5d64452`, `config/rules-opinionated.neon`, result cache cleared.
- **Replay:** `vendor/bin/phpstan analyze -c config/rules-opinionated.neon docs/exploratory-testing/2026-10-24-phpstan-extension-accessing-globals/evidence/repro-class-constant-operand.php --level=0 --no-progress --error-format=raw`
- **Expected:** `constant.class` on lines 13, 18 and 24. This follows from the README ("accessing a constant on another class") and the false negative already named in #121.
- **Actual:** only line 13 (`constant.class`) and line 29 (`property.static`) are reported.
- **Repeats:** 2 of 2 runs, cache cleared each time ([`repro-class-constant-operand-run.txt`](evidence/repro-class-constant-operand-run.txt)).
- **Cause:** `processClassConstFetch()` returns early when `$node->class` is not a `Name`. #121's merged refactor did not wire `ClassOperandResolver` into this rule, and open #96 currently keeps the exclusion.

### 2. A class's own constant is reported as external when the class name's case differs (filed as [#130](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/130))

- **Impact:** a false `constant.class` that calls a self-reference a hidden external dependency. Severity is low because PHPStan core also reports `class.nameCase` on the same line.
- **Starting state:** as above.
- **Replay:** `php docs/exploratory-testing/2026-10-24-phpstan-extension-accessing-globals/evidence/repro-class-constant-case.php` prints `int(10)`, so PHP resolves `CLIENT` to `Client`. Then run `vendor/bin/phpstan analyze -c config/rules-opinionated.neon docs/exploratory-testing/2026-10-24-phpstan-extension-accessing-globals/evidence/repro-class-constant-case.php --level=0 --no-progress --error-format=raw`.
- **Expected:** no `constant.class` on line 14, matching line 9 and the rule's own `constant('CLIENT::LIMIT')` path, which compares case-insensitively.
- **Actual:** `constant.class` for `CLIENT::LIMIT` on line 14.
- **Repeats:** 2 of 2 runs, cache cleared each time ([`repro-class-constant-case-run.txt`](evidence/repro-class-constant-case-run.txt)).

## Rejected and unresolved candidates

- **Rejected: stale diagnostics after switching to strict-only.** After I set the installer `ignore`, the first strict run still showed `access.superglobal.nested`, which belongs to the default rules. `phpstan clear-result-cache` removed it, and `phpstan diagnose` showed no installed extensions. PHPStan's result cache kept results from the earlier configuration; the extension did not load the default rules. My first attempt also used `composer dump-autoload`, which does not regenerate the installer config. That was a driver error, and I repeated the step with `composer install`.
- **Rejected: `call_user_func('rand')` and `array_map('microtime', …)`.** These are out of scope according to #115.
- None unresolved.

## Usability observations

- `HasLimit::LIMIT`, read inside a class that implements `HasLimit`, is reported as `constant.class`, but `self::LIMIT` for the same constant is not. Users may see this as noise. A suggested improvement is to treat constants inherited from the class's own interfaces or parents like `self::`.
- `new \DateTimeImmutable()` reads the clock but is outside `ForbidImpureGlobalFunctions`, which covers functions only. The README describes the rule as covering functions, so this is a scope note, not a defect.
- When switching rulesets, users may need `phpstan clear-result-cache`. The README's strict-only instructions could mention this.
- `ForbidUsingGlobalConstants` still reports built-in constants such as `PHP_SAPI`, as noted in the 2026-10-03 pass.

## Limits

- The CI Docker images (PHP 8.3/8.4) were not exercised. All runs used local PHP 8.5.9.
- The consumer project resolved PHPStan 2.3.1, not the lockfile's 2.2.13. Bundled fixtures and repros were run with 2.2.13.
- No product source files were changed.
