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

## Installation

You can install this extension via [Composer](https://getcomposer.org/):

```bash
composer require --dev jonbaldie/phpstan-extension-accessing-globals
```

## Usage

To enable the rules, include the `rules.neon` file in your project's `phpstan.neon` configuration:

```neon
includes:
    - vendor/jonbaldie/phpstan-extension-accessing-globals/config/rules.neon
```

By default, all rules are enabled. If you only want to enable specific rules, you can copy the ones you need from the extension's `config/rules.neon` file into your project's `phpstan.neon`.

## Example

This extension is designed to catch code like the following:

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
    // BAD: Fails `neverAccessGlobals`
    global $db, $config;

    // BAD: Fails `neverModifyGlobals`
    $GLOBALS['db'] = new PDO(
        'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['database'],
        $config['database']['username'],
        $config['database']['password']
    );

    // BAD: Fails `neverAccessSuperGlobals`
    $user = $_SESSION['user'];

    // BAD: Fails `neverModifySuperGlobals`
    $_SESSION['user'] = $user;
}
```

Instead of relying on global state, you should pass dependencies explicitly to your functions and classes.