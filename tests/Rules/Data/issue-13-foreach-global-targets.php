<?php

declare(strict_types=1);

foreach ([1] as $GLOBALS['root']) {
}

foreach ([1] as $GLOBALS['outer']['inner']) {
}

foreach ($GLOBALS['iterable'] as $GLOBALS['key'] => $GLOBALS['value']) {
}

foreach ([1] as [$_GET['ignored'], $GLOBALS['destructured']]) {
}
