// WP BB Platform: Redis object-cache stability settings.
// Add after WP_REDIS_HOST / WP_REDIS_PORT / WP_REDIS_PREFIX in config/application.php.
Config::define('WP_REDIS_CLIENT', env('REDIS_CLIENT') ?: 'phpredis');
Config::define('WP_REDIS_DATABASE', (int) (env('REDIS_DATABASE') ?: 0));
Config::define('WP_REDIS_TIMEOUT', (float) (env('REDIS_TIMEOUT') ?: 1));
Config::define('WP_REDIS_READ_TIMEOUT', (float) (env('REDIS_READ_TIMEOUT') ?: 1));
Config::define('WP_REDIS_FLUSH_TIMEOUT', (float) (env('REDIS_FLUSH_TIMEOUT') ?: 2));
Config::define('WP_REDIS_RETRY_INTERVAL', (int) (env('REDIS_RETRY_INTERVAL') ?: 100));
Config::define('WP_REDIS_MAXTTL', (int) (env('REDIS_MAXTTL') ?: DAY_IN_SECONDS));
Config::define('WP_REDIS_DISABLE_METRICS', true);
Config::define('WP_REDIS_DISABLED', (bool) (env('WP_REDIS_DISABLED') ?: false));
