# Exploratory testing of PHPStan rule sets

Date: 2026-11-07

## Setup

The pass used the PHPStan CLI and the repository's published rule configurations at commit `5b32ef7` (release v0.6.9). PHP was 8.5.4, Composer was 2.9.5, and the lockfile selected PHPStan 2.3.4. `composer install --no-interaction --prefer-dist --no-progress` installed the locked dependencies into an isolated worktree. The Composer cache, temporary files, and generated baseline also stayed inside that worktree. The [install log](evidence/install.txt) records the packages.

`vendor/bin/phpunit` passed 166 tests and 479 assertions. See the [test output](evidence/phpunit.txt). No background processes, browsers, containers, images, or volumes were started.

## Default rules and a legacy baseline

The goal was to allow entry-point superglobals while detecting hidden state inside a function. In [entry.php](evidence/entry.php), the default rules allowed the root read and write, but reported the nested global declaration, its mutation, and the nested superglobal operations. The [output](evidence/rules.txt) contains five diagnostics and exit code 1. A by-reference call that mutates `$_SESSION` produced both access and mutation diagnostics in the [variation](evidence/variation-rules.txt).

The explicit-argument [variation](evidence/explicit.php) produced no findings and exited 0 under all three rule sets. See [explicit.txt](evidence/explicit.txt).

For the lasting effect, PHPStan generated a baseline with five entries and then exited 0 when the configuration included that baseline. The [baseline log](evidence/baseline.txt) includes the generated file content. The first attempt used the wrong relative include path and failed before analysis. The log retains that driver error and the corrected run. The generated baseline is scratch data, not a tracked file.

## Strict rules at root and nested scope

The goal was to detect the same hidden state even at the entry point. Against the same [entry.php](evidence/entry.php), the [strict output](evidence/rules-strict.txt) contains eight diagnostics, including the root read and write, and exits 1. The [by-reference variation](evidence/variation-rules-strict.txt) reports access and mutation of `$_SESSION`. The explicit-argument variation exits 0 under strict rules.

## Opinionated rules and indirect expressions

The goal was to detect implicit dependencies and permit explicit arguments. The [functional fixture](evidence/functional.php) produced one diagnostic each for `time()`, a global constant, an external class constant, and a static property. The [output](evidence/opinionated.txt) exits 1. The explicit-argument fixture exits 0.

The [indirect variation](evidence/variations.php) calls `time` and `constant` through known strings and reads a class constant through a class-string. PHPStan reports all three dependencies in the [output](evidence/variation-rules-opinionated.txt). This also checks behavior previously tracked in [#115](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/115) and [#129](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/129).

## Findings and limits

The [decision log](evidence/decisions.tsv) records the main choices and was written after the runs. No product bugs were confirmed and no new issues were filed. The baseline configuration error was a test-driver problem, not a product failure. There are no unresolved candidates from these journeys.

Both the strict rules and the default nested-scope rules report access and mutation for a superglobal write. The README notes that one operation may trigger multiple rules, so this result was not treated as a bug. The related issue [#124](https://github.com/jonbaldie/phpstan-extension-accessing-globals/issues/124) discusses duplicate `$GLOBALS` mutation diagnostics, a different case.

This pass did not install the extension into a separate consumer project, test PHP 8.3 or 8.4, or exercise every mutation form. It did not generate baselines for the strict or opinionated rules, or rerun the documented fixture totals. The [previous exploratory pass](../2026-10-24-phpstan-extension-accessing-globals/README.md) covers a consumer installation. The CI workflow covers PHP 8.3 and 8.4 separately. No product code was changed.
