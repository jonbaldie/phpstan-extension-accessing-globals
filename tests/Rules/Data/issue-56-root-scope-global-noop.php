<?php

global $harmlessNoop;

(static function (): void {
    global $nestedGlobal;
})();
