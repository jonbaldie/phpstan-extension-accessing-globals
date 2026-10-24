<?php

namespace App {
    $page = $_GET['page'] ?? 1;          // L4: braced namespace at file scope (root)
}

namespace {
    if (PHP_SAPI !== 'cli') {
        $_SESSION['seen'] = true;        // L9: root-scope if block
    }
    try {
        $id = $_POST['id'];              // L12: root-scope try block
    } finally {
    }
    $handler = new class {
        public function run(): string
        {
            return $_GET['q'];           // L18: method of anonymous class -> nested
        }
    };
    if (true) {
        function conditionallyDeclared(): string
        {
            return $_COOKIE['c'];        // L24: conditionally declared function -> nested
        }
    }
}
