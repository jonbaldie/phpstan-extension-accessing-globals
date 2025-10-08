This project is a PHPStan extension for detecting code that accesses or modifies globally shared data in PHP.

This extension enables a series of rules:

- `neverAccessGlobals`: never allow code to read from data outside of its scope
- `neverModifyGlobals`: never allow code to modify data outside of its scope
- `neverAccessSuperGlobals`: never allow code to access superglobals (weaker version of `neverAccessGlobals`)
- `neverModifySuperGlobals`: never allow code to modify superglobals (weaker version of `neverModifyGlobals`)
- `neverAccessSuperGlobalsInNestedScope`: allows accessing superglobals in the root scope, but not in nested scopes
- `neverModifySuperGlobalsInNestedScope`: allows modifying superglobals in the root scope, but not in nested scopes

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

```bash
vendor/bin/phpunit
```

### Running static analysis

```bash
vendor/bin/phpstan analyse src tests
```
