# Exploratory testing: phpstan-extension-accessing-globals

Date: 2026-09-26

## Scope and setup

I drove the extension through its public PHPStan CLI using the repository's three published rule configurations and deliberately violating fixtures. The checkout started clean at `origin/main` commit `896c622`.

- PHP 8.4.1
- Composer 2.8.3
- PHPStan 2.2.13
- PHPUnit 12.5.34
- Evidence directory: [`evidence/`](evidence/)
- `vendor/bin/phpunit`: 109 tests and 230 assertions passed; output is in [`phpunit.txt`](evidence/phpunit.txt).

The first analyze attempts failed before fixture analysis because the local optimized Composer class map did not resolve the repository's rule classes. `composer dump-autoload --no-interaction --optimize` repaired the local autoloader; the initial failure and repair are recorded in [`initial-autoload-failure.txt`](evidence/initial-autoload-failure.txt). The subsequent journeys all used the same code and configuration.

PHPStan's trailing spaces used only for table alignment were trimmed from the text captures; diagnostic messages, identifiers, counts, and recorded exit codes are preserved.

## Journeys

### Default rules: globals and nested superglobals

**Goal:** Detect global access and modification, and superglobal access and modification in nested scopes while allowing root-scope superglobals.

The documented default aggregate command completed with exit code 1 and exactly 36 diagnostics, as expected: [`default-rules.txt`](evidence/default-rules.txt). A root-scope probe reading `$_GET` and modifying `$_SESSION` completed with exit code 0 under `config/rules.neon`: [`default-root.txt`](evidence/default-root.txt).

The default rules also produced 28 diagnostics when run against the strict superglobal fixtures, which place their accesses and writes inside `function test()`: [`default-nested-fixtures.txt`](evidence/default-nested-fixtures.txt).

### Strict rules: superglobals at every scope

**Goal:** Reject all superglobal reads and writes, including root-scope use.

The documented strict aggregate command completed with exit code 1 and 28 diagnostics, as expected: [`strict-rules.txt`](evidence/strict-rules.txt). The same root-scope probe produced three diagnostics under `config/rules-strict.neon` (one `$_GET` read and both access and modification of `$_SESSION`): [`strict-root.txt`](evidence/strict-root.txt).

The manual notes describe `tests/Rules/Data/access-superglobals.php` as testing root-scope behavior, but all its reads are inside `function test()`. That command establishes nested-scope behavior only. I filed [issue #89](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/89) with the fixture and root-probe evidence.

### Opinionated rules: constants, static properties, and impure functions

**Goal:** Detect the configured opinionated rule violations using the combined verification command and confirm the individual class-constant fixture.

The combined command exited 1 and reported 13 diagnostics on both runs: [`opinionated-rules.txt`](evidence/opinionated-rules.txt), [`opinionated-rules-run-2.txt`](evidence/opinionated-rules-run-2.txt). Eleven are the intended extension findings: three `constant.global`, two `property.static`, two `constant.class`, and four `function.impure`. The remaining two are `classConstant.notFound` errors for `Config::TIMEOUT` on lines 45 and 50 of `using-class-constants.php`.

`using-static-properties.php` and `using-class-constants.php` both declare `AccessingGlobals\Tests\Rules\Data\Config`, with different members. Analyzing the class-constant fixture alone produces only its two intended `constant.class` findings: [`class-constants-alone-json.txt`](evidence/class-constants-alone-json.txt). A core-only config produces no errors for that fixture: [`class-constants-core-only.txt`](evidence/class-constants-core-only.txt). The duplicate fixture declarations explain the two extra diagnostics in the aggregate run. I filed [issue #88](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/88).

I also probed the impure-function list covered by existing [issue #75](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/75). The evidence fixture calls 14 functions named in that issue inside a function, with `time()` as a positive control. Two identical runs each report only `time()` and no candidate function: [`issue-75-probe.php`](evidence/issue-75-probe.php), [`issue-75-run-1.txt`](evidence/issue-75-run-1.txt), [`issue-75-run-2.txt`](evidence/issue-75-run-2.txt). I added the independent reproduction to [issue #75](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/75#issuecomment-5841879460).

## Findings and limits

- **Confirmed existing rule bug:** The 14 missing impure-function detections from issue #75 reproduce twice while `time()` is detected as a control.
- **Confirmed verification issue:** The opinionated aggregate combines same-namespace `Config` fixtures, adding two PHPStan `classConstant.notFound` diagnostics; issue #88 tracks it.
- **Confirmed verification description issue:** The strict fixture described as root-scope is actually inside a function; issue #89 tracks it. A separate root-scope probe verified that strict reports those accesses while default permits them.
- **Rejected setup candidate:** The initial class-not-found output came from stale local Composer autoload state, and no failures remained after regenerating the optimized autoloader.

The ordinary path and a relevant scope variation were exercised for each rule set. The pass did not exhaust the extension's full edge-case space or test PHP versions other than 8.4.1. No product source files were changed during this pass; the PHP snippets and command output in `evidence/` are retained to replay the findings.
