<?php

use Roots\WPConfig\Config;
use function Env\env;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?: true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);
Config::define('DISALLOW_INDEXING', true);
Config::define('DISALLOW_FILE_MODS', false);

/* Local Docker/WSL: allow WordPress to write directly */
Config::define('FS_METHOD', 'direct');
// PHP error log
ini_set('display_errors', '0');