<?php

function checkKnownMissingImpureFunctions(): void
{
    idate('Y');
    srand(1);
    mt_srand(1);
    lcg_value();
    get_cfg_var('memory_limit');
    ini_restore('display_errors');
    set_time_limit(0);
    error_clear_last();
    clearstatcache();
    session_cache_expire();
    session_cache_limiter();
    session_module_name();
    realpath_cache_get();
    realpath_cache_size();

    time(); // Known rule entry used as positive control.
}
