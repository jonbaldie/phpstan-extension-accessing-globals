# PHPStan Extension: Accessing Globals

This project is a [PHPStan](https://phpstan.org/) extension for detecting code that accesses or modifies globally shared data in PHP, a common source of bugs and a violation of functional programming principles.

It helps you write cleaner, more predictable code by ensuring that functions and methods receive all their dependencies explicitly.

## Features

This extension provides a set of rules to enforce restrictions on accessing and modifying global and superglobal variables:

-   **`neverAccessGlobals`**: Disallows reading from global variables using the `global` keyword.
-   **`neverModifyGlobals`**: Prevents modification of the `$GLOBALS` superglobal array.
-   **`neverAccessSuperGlobals`**: Forbids accessing superglobal variables like `$_GET`, `$_POST`, `$_SESSION`, etc.
-   **`neverModifySuperGlobals`**: Forbids modifying superglobal variables.
-   **`neverAccessSuperGlobalsInNestedScope`**: A weaker version of `neverAccessSuperGlobals` that allows accessing superglobals in the root scope (e.g., in an `index.php` file) but not within functions, methods, or closures.
-   **`neverModifySuperGlobalsInNestedScope`**: A weaker version of `neverModifySuperGlobals` that allows modifying superglobals in the root scope.

The extension also includes a more "opinionated" set of rules for teams that want to enforce a stricter, more functional style of programming:

-   **`ForbidUsingGlobalConstants`**: Prevents functions from accessing global constants (`define()` or `const`), forcing them to be passed as arguments.
-   **`ForbidUsingStaticProperties`**: Prevents access to static properties, which are a form of global state.
-   **`ForbidUsingClassConstants`**: Prevents a function from accessing a constant on another class, enforcing that the value should be passed in.
-   **`ForbidImpureGlobalFunctions`**: Flags calls to impure global functions like `time()`, `getenv()`, or `rand()` that produce side effects or rely on hidden external state.

## Installation

You can install this extension via [Composer](https://getcomposer.org/):

```bash
composer require --dev jonbaldie/phpstan-extension-accessing-globals
```

## Usage

This extension comes with three pre-defined rule sets.

### Default (Pragmatic) Rules

This is the recommended configuration for most projects. It prevents the use of the `global` keyword and modification of `$GLOBALS`, while allowing superglobals to be accessed and modified in the root scope (e.g., in your `index.php` file).

To enable the default rules, include `rules.neon` in your project's `phpstan.neon` configuration:

```neon
includes:
    - vendor/jonbaldie/phpstan-extension-accessing-globals/config/rules.neon
```

This enables the following rules:
- `neverAccessGlobals` - Detects use of the `global` keyword
- `neverModifyGlobals` - Detects modifications via `$GLOBALS['key']`
- `neverModifyGloballyDeclaredVariables` - Detects modifications to variables declared with `global`
- `neverAccessSuperGlobalsInNestedScope` - Allows superglobals in root scope only (accessing)
- `neverModifySuperGlobalsInNestedScope` - Allows superglobals in root scope only (modifying)

**Note:** A single line of code may trigger multiple rules. For example, `global $db; $db = new PDO(...)` will report both accessing the global variable AND modifying it, since these represent distinct problems that need different refactoring approaches.

### Strict Rules

This configuration is for projects that want to enforce the highest level of strictness. It completely forbids any interaction with global or superglobal variables anywhere in your codebase.

To enable the strict rules, include `rules-strict.neon` instead:

```neon
includes:
    - vendor/jonbaldie/phpstan-extension-accessing-globals/config/rules-strict.neon
```

This enables the following rules:
- `neverAccessGlobals`
- `neverModifyGlobals`
- `neverAccessSuperGlobals`
- `neverModifySuperGlobals`

### Opinionated Rules

This is the strictest rule set, designed for projects that want to enforce a purely functional style where all dependencies are explicit. It helps eliminate hidden dependencies, side effects, and mutations.

To enable the opinionated rules, include `rules-opinionated.neon`:

```neon
includes:
    - vendor/jonbaldie/phpstan-extension-accessing-globals/config/rules-opinionated.neon
```

This enables the following rules:
- `ForbidUsingGlobalConstants`
- `ForbidUsingStaticProperties`
- `ForbidUsingClassConstants`
- `ForbidImpureGlobalFunctions`

### Working with Legacy Code

If you're applying this extension to an existing codebase with heavy global variable usage, here's what to expect:

**Error Volume:** A 3,000-line legacy script might have 50-100+ errors from the default rules. This is expected and represents real technical debt.

**Recommended Approach:**
1. **Start with the default rules** (`rules.neon`) - they're pragmatic and focus on the most problematic patterns
2. **Focus on `global` keyword usage first** - this reveals which functions share hidden state with each other
3. **Tackle superglobal usage in functions next** - superglobals at the top-level (e.g., in `index.php`) are allowed, but inside functions they create hidden dependencies
4. **Skip the opinionated rules initially** - they'll flag hundreds of issues like `time()`, `rand()`, and `define()` usage that aren't the critical problems

**What each rule catches:**
- `neverAccessGlobals` + `neverModifyGlobals` → Functions using `global $x` or `$GLOBALS['x']` (the worst offenders)
- `neverModifyGloballyDeclaredVariables` → Assignments to variables after declaring them with `global`
- Superglobal rules → `$_GET`, `$_POST`, `$_SESSION` usage inside functions (root scope access is allowed)

The goal is to make dependencies explicit through function parameters and return values.

### Custom Configuration

If you want to enable only a specific set of rules, you can copy the ones you need from the extension's `config` directory into your own `phpstan.neon` file.

## Examples

This extension is designed to catch problematic patterns in your code. Here's what each rule detects:

### Default Rules (`rules.neon`)

```php
<?php

// Root-level variables that will be accessed globally
$db = null;
$config = ['host' => 'localhost'];

// ❌ BAD: neverAccessGlobals
function connectDatabase(): void {
    global $db, $config;  // Accessing global variables - hidden dependency!
    // ...
}

// ❌ BAD: neverModifyGlobals
function setCache(): void {
    $GLOBALS['cache'] = new Cache();  // Modifying via $GLOBALS array
}

// ❌ BAD: neverModifyGloballyDeclaredVariables
function initDb(): void {
    global $db;           // Accessing global (first error)
    $db = new PDO(...);   // Modifying it (second error)
}

// ❌ BAD: neverAccessSuperGlobalsInNestedScope
function getUserId(): int {
    return $_SESSION['user_id'];  // Accessing superglobal in function - hidden dependency!
}

// ❌ BAD: neverModifySuperGlobalsInNestedScope
function login(User $user): void {
    $_SESSION['user_id'] = $user->id;  // Modifying superglobal in function
}

// ✅ GOOD: Root scope superglobal access is allowed
$userId = $_SESSION['user_id'] ?? null;
$requestId = $_GET['id'] ?? null;

// ✅ GOOD: Pass dependencies explicitly
function connectDatabase(array $config): PDO {
    return new PDO($config['host'], $config['user'], $config['pass']);
}

function getUserId(array $session): int {
    return $session['user_id'];
}

function login(User $user): array {
    return ['user_id' => $user->id];  // Return new state instead of mutating
}
```

### Strict Rules (`rules-strict.neon`)

```php
<?php

// ❌ BAD: Even root-scope superglobal access is forbidden
$userId = $_SESSION['user_id'];  // neverAccessSuperGlobals
$_SESSION['last_seen'] = time();  // neverModifySuperGlobals

// ✅ GOOD: Wrap superglobals in a service/class at entry point, inject it
class Request {
    public function __construct(private array $query) {}
    public function get(string $key): mixed { return $this->query[$key] ?? null; }
}

$request = new Request($_GET);  // Wrap once at entry point
processRequest($request);        // Pass it explicitly
```

### Opinionated Rules (`rules-opinionated.neon`)

```php
<?php

define('API_KEY', 'secret123');

class Config {
    public static $timeout = 30;
    public const MAX_RETRIES = 3;
}

// ❌ BAD: ForbidUsingGlobalConstants
function callApi(): void {
    $key = API_KEY;  // Hidden dependency on global constant
}

// ❌ BAD: ForbidUsingStaticProperties
function makeRequest(): void {
    $timeout = Config::$timeout;  // Global state via static property
}

// ❌ BAD: ForbidUsingClassConstants
function retry(): void {
    for ($i = 0; $i < Config::MAX_RETRIES; $i++) { /* ... */ }
}

// ❌ BAD: ForbidImpureGlobalFunctions
function generateId(): string {
    return time() . '-' . rand();  // Hidden dependencies on system clock and RNG
}

// ✅ GOOD: Pass values explicitly
function callApi(string $apiKey): void {
    // Use $apiKey parameter
}

function makeRequest(int $timeout): void {
    // Use $timeout parameter
}

function generateId(int $timestamp, int $random): string {
    return $timestamp . '-' . $random;  // Deterministic, testable
}
```

The key principle: **Make all dependencies explicit through function parameters and return values.**