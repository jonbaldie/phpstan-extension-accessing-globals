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

**⚠️ CRITICAL: All commands below are EXPECTED TO FAIL (exit code 1).** 

**This is CORRECT behavior!** ✅

The test files contain deliberately bad code. When PHPStan detects violations and exits with code 1, it proves the rules are working correctly.

---

#### Understanding Test Results

✅ **SUCCESS = Exit Code 1 + Error Messages**
- Command exits with code 1
- Shows "[ERROR] Found X errors"
- Lists specific violations with identifiers (e.g., `🪪 access.global`)

❌ **FAILURE = Exit Code 0 (No Errors)**
- Command exits with code 0
- Shows "No errors found"
- This means the rule is BROKEN and not detecting violations

---

#### Basic Rules (config/rules.neon)

**Expected:** Each command should exit with code 1 and show the specified number of errors.

```bash
# Expected: 3 errors (accessing global variables $foo, $bar, $baz)
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/access-globals.php --level=0 --no-progress

# Expected: 5 errors (accessing $db via global keyword, modifying $db, modifying via $GLOBALS)
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/modify-globals.php --level=0 --no-progress

# Expected: 9 errors (accessing all superglobals in nested scopes)
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/access-superglobals-in-nested-scope.php --level=0 --no-progress

# Expected: 19 errors (modifying all superglobals in nested scopes)
vendor/bin/phpstan analyze -c config/rules.neon tests/Rules/Data/modify-superglobals-in-nested-scope.php --level=0 --no-progress
```

<details>
<summary>Example successful output (click to expand)</summary>

```
------ ------------------------------------------------------------------
  Line   access-globals.php                                          
 ------ ------------------------------------------------------------------
  5      Code is accessing global variable $foo. Use dependency injection
         instead.                                                    
         🪪  access.global                                           
  10     Code is accessing global variable $bar. Use dependency injection
         instead.                                                    
         🪪  access.global                                           
  10     Code is accessing global variable $baz. Use dependency injection
         instead.                                                    
         🪪  access.global                                           
 ------ ------------------------------------------------------------------

 [ERROR] Found 3 errors
```
</details>

---

#### Strict Rules (config/rules-strict.neon)

**Expected:** Each command should exit with code 1 and show the specified number of errors.

```bash
# Expected: 9 errors (accessing any superglobal, even in root scope)
vendor/bin/phpstan analyze -c config/rules-strict.neon tests/Rules/Data/access-superglobals.php --level=0 --no-progress

# Expected: 19 errors (modifying any superglobal, even in root scope)
vendor/bin/phpstan analyze -c config/rules-strict.neon tests/Rules/Data/modify-superglobals.php --level=0 --no-progress
```

<details>
<summary>Example successful output (click to expand)</summary>

```
------ -----------------------------------------------------------------------
  Line   access-superglobals.php                                     
 ------ -----------------------------------------------------------------------
  5      Code is accessing superglobal variable $_GET. Use a wrapper service
         instead.                                                    
         🪪  access.superglobal                                      
  6      Code is accessing superglobal variable $_POST. Use a wrapper service
         instead.                                                    
         🪪  access.superglobal                                      
 ------ -----------------------------------------------------------------------

 [ERROR] Found 9 errors
```
</details>

---

#### Opinionated Rules (config/rules-opinionated.neon)

**Expected:** Each command should exit with code 1 and show the specified number of errors.

```bash
# Expected: 3 errors (accessing global constants MY_CONSTANT, ANOTHER_CONSTANT)
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-global-constants.php --level=0 --no-progress

# Expected: 2 errors (accessing static property Config::$value)
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-static-properties.php --level=0 --no-progress

# Expected: 2 errors (accessing constants from external classes)
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-class-constants.php --level=0 --no-progress

# Expected: 4 errors (calling time(), rand(), getenv(), file_get_contents())
vendor/bin/phpstan analyze -c config/rules-opinionated.neon tests/Rules/Data/using-impure-functions.php --level=0 --no-progress
```

<details>
<summary>Example successful output (click to expand)</summary>

```
------ -----------------------------------------------------------------------
  Line   using-impure-functions.php                                  
 ------ -----------------------------------------------------------------------
  12     Code is calling the impure function "time()". This creates a hidden
         dependency on external state; pass the result as an argument instead.
         🪪  function.impure                                         
  13     Code is calling the impure function "rand()". This creates a hidden
         dependency on external state; pass the result as an argument instead.
         🪪  function.impure                                         
 ------ -----------------------------------------------------------------------

 [ERROR] Found 4 errors
```
</details>

---

#### Quick Verification (All at Once)

```bash
# Expected: 36 errors total across all basic rule violations
vendor/bin/phpstan analyze -c config/rules.neon \
  tests/Rules/Data/access-globals.php \
  tests/Rules/Data/modify-globals.php \
  tests/Rules/Data/access-superglobals-in-nested-scope.php \
  tests/Rules/Data/modify-superglobals-in-nested-scope.php \
  --level=0 --no-progress

# Expected: 28 errors total across all strict rule violations
vendor/bin/phpstan analyze -c config/rules-strict.neon \
  tests/Rules/Data/access-superglobals.php \
  tests/Rules/Data/modify-superglobals.php \
  --level=0 --no-progress

# Expected: 13 errors total across all opinionated rule violations
vendor/bin/phpstan analyze -c config/rules-opinionated.neon \
  tests/Rules/Data/using-global-constants.php \
  tests/Rules/Data/using-static-properties.php \
  tests/Rules/Data/using-class-constants.php \
  tests/Rules/Data/using-impure-functions.php \
  --level=0 --no-progress
```

---

#### Troubleshooting

**If you see exit code 0 (no errors):**
- ❌ The rule is broken
- Check if the rule is properly registered in the config file
- Verify the rule implementation

**If you see different error counts:**
- ❌ The rule may have a bug or the test file was modified
- Compare actual output with expected counts above
- Run `vendor/bin/phpunit` to verify unit tests still pass
