<?php

namespace AccessingGlobals\Tests\Rules\Data;

function inspectEnvironmentAndFilesystem(): void
{
    $user = get_current_user(); // Impure: reads the script owner from the OS

    $path = get_include_path(); // Impure: reads runtime include_path setting

    $canonical = realpath('/var/log'); // Impure: resolves symlinks via the filesystem

    $exec = is_executable('/bin/bash'); // Impure: checks file permissions on the filesystem
}
