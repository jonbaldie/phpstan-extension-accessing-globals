<?php

// Root-scope mutations are checked by the strict rule.
unset($_GET['root']);
unset($GLOBALS['root']);

function unsetMultipleTargets(): void
{
    unset($_POST['nested'], $_SESSION['nested']['deep'], $GLOBALS['outer']['inner']);
}
