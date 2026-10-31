<?php

declare(strict_types=1);

function issue75ImpureFunctions(): void
{
    idate('Y');
    srand();
    mt_srand();
    lcg_value();
    openssl_random_pseudo_bytes(16);
    get_cfg_var('test');
    get_browser();
    zend_version();
    ini_restore('display_errors');
    set_time_limit(1);
    error_clear_last();
    clearstatcache();
    assert_options(1);
    set_error_handler(null);
    set_exception_handler(null);
    openlog('test', 0, 0);
    closelog();
    getlastmod();
    get_meta_tags('test');
    md5_file('test');
    sha1_file('test');
    hash_file('sha256', 'test');
    getimagesize('test');
    readgzfile('test');
    session_cache_expire();
    session_cache_limiter();
    session_module_name();
    session_save_path();
    session_set_save_handler(null);
    realpath_cache_get();
    realpath_cache_size();
    strlen('pure');
}
