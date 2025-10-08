This project is a PHPStan extension for detecting code that accesses or modifies globally shared data in PHP.

This extension enables a series of rules:

- `neverAccessGlobals`: never allow code to read from data outside of its scope
- `neverModifyGlobals`: never allow code to modify data outside of its scope
- `neverAccessSuperGlobals`: never allow code to access superglobals (weaker version of `neverAccessGlobals`)
- `neverModifySuperGlobals`: never allow code to modify superglobals (weaker version of `neverModifyGlobals`)
- `neverAccessSuperGlobalsInNestedScope`: allows accessing superglobals in the root scope, but not in nested scopes
- `neverModifySuperGlobalsInNestedScope`: allows modifying superglobals in the root scope, but not in nested scopes

It also includes a more opinionated set of rules to enforce a stricter functional style:

- `ForbidUsingGlobalConstants`: prevents accessing global constants.
- `ForbidUsingStaticProperties`: prevents accessing static properties.
- `ForbidUsingClassConstants`: prevents accessing constants on other classes.
- `ForbidImpureGlobalFunctions`: prevents calls to impure global functions (e.g., `time()`, `getenv()`).
- `EnforceImmutableObjectUpdates`: prevents "fire-and-forget" method calls on passed-in objects.

It's intended to detect code like this:

```php
<?php

$db = null;
$config = [
    'database' => [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'my_database'
    ]
];

function initializeApp(): void {
    global $db, $config; // BAD, accessing globals - fails `neverAccessGlobals`

    $db = new PDO(
        'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['database'],
        $config['database']['username'],
        $config['database']['password']
    ); // BAD, modifying global $db - fails `neverModifyGlobals`

    // BAD, accessing superglobals - fails `neverAccessSuperGlobals`
    $user = $_SESSION['user'];

    // BAD, modifying superglobals - fails `neverModifySuperGlobals`
    $_SESSION['user'] = $user;
}
```

## Development

To work on this project, you'll need Composer.

### Installation

```bash
composer install
```

### Running tests

Please run the unit tests whenever you make changes to the codebase.

```bash
vendor/bin/phpunit
```

### Manual Verification Commands

**IMPORTANT: These commands are expected to FAIL with specific errors.** This is the correct behavior!

The test data files in `tests/Rules/Data/` contain deliberately bad code. Running PHPStan on them verifies that our rules correctly detect and report violations.

#### Basic Rules (config/rules.neon)

These should each fail with errors showing the rules are detecting violations:

```bash
# SHOULD FAIL: Detecting global variable access
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/access-globals.php --level=0 --no-progress

# SHOULD FAIL: Detecting global variable modification
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/modify-globals.php --level=0 --no-progress

# SHOULD FAIL: Detecting superglobal access in nested scopes
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/access-superglobals-in-nested-scope.php --level=0 --no-progress

# SHOULD FAIL: Detecting superglobal modification in nested scopes
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/modify-superglobals-in-nested-scope.php --level=0 --no-progress
```

#### Strict Rules (config/rules-strict.neon)

These should each fail with errors showing stricter superglobal detection:

```bash
# SHOULD FAIL: Detecting ANY superglobal access
vendor/bin/phpstan analyze -c config/rules-strict.neon tests/Rules/Data/access-superglobals.php --level=0 --no-progress

# SHOULD FAIL: Detecting ANY superglobal modification
vendor/bin/phpstan analyze -c config/rules-strict.neon tests/Rules/Data/modify-superglobals.php --level=0 --no-progress
```

#### Opinionated Rules (config/rules-opinionated.neon)

These should each fail with errors showing the stricter functional programming rules:

```bash
# SHOULD FAIL: Detecting global constant access
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-global-constants.php --level=0 --no-progress

# SHOULD FAIL: Detecting static property access
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-static-properties.php --level=0 --no-progress

# SHOULD FAIL: Detecting external class constant access
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-class-constants.php --level=0 --no-progress

# SHOULD FAIL: Detecting impure function calls
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-impure-functions.php --level=0 --no-progress
```

#### Quick Verification (All at Once)

```bash
# Test all basic rules together - SHOULD FAIL with multiple errors
vendor/bin/phpstan analyze -c config/rules.neon \
  tests/Rules/Data/access-globals.php \
  tests/Rules/Data/modify-globals.php \
  tests/Rules/Data/access-superglobals-in-nested-scope.php \
  tests/Rules/Data/modify-superglobals-in-nested-scope.php \
  --level=0 --no-progress

# Test all strict rules together - SHOULD FAIL with multiple errors
vendor/bin/phpstan analyze -c config/rules-strict.neon \
  tests/Rules/Data/access-superglobals.php \
  tests/Rules/Data/modify-superglobals.php \
  --level=0 --no-progress

# Test all opinionated rules together - SHOULD FAIL with multiple errors
vendor/bin/phpstan analyze -c config/rules-opinionated.neon \
  tests/Rules/Data/using-global-constants.php \
  tests/Rules/Data/using-static-properties.php \
  tests/Rules/Data/using-class-constants.php \
  tests/Rules/Data/using-impure-functions.php \
  --level=0 --no-progress
```

**Expected Result:** Each command exits with code 1 and displays specific error messages showing which violations were detected. If a command exits with code 0 (success), that means the rule is NOT working correctly.
