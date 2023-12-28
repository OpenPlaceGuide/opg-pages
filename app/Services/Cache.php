<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Log;

class Cache
{
    const LIFETIME = 86400;

    public static function getCacheMiddleware()
    {
        return 'cache.headers:public;max_age=' . self::LIFETIME;
    }

    public static function remember(string $key, \Closure $callback): mixed
    {
        static $logged = false;
        static $flushedKeys = [];

        if (request()->header('cache-control') === 'no-cache') {
            if (!$logged) {
                Log::notice(sprintf('Cache flush header received from %s', request()->ip()));
                $logged = true;
            }
            if (!isset($flushedKeys[$key])) {
                CacheFacade::forget($key);
                $flushedKeys[$key] = true;  // avoid flushing the same key twice in a request
            }
        }

        return CacheFacade::remember($key, self::LIFETIME, $callback);
    }
}
