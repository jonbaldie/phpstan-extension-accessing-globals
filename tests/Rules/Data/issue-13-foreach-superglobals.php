<?php

declare(strict_types=1);

foreach ([1] as $_GET['root']) {
}

foreach ([1] as $_POST['outer']['inner']) {
}

foreach ($GLOBALS['iterable'] as $GLOBALS['key'] => $_REQUEST['value']) {
}

foreach ([1] as [$_SESSION['first'], $_COOKIE['second']['nested']]) {
}

function nestedScope(): void
{
    foreach ([1] as [$_FILES['nested'], $_ENV['deep']['nested']]) {
    }
}
