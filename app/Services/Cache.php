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

        if (self::flushRequested()) {
            if (!$logged) {
                Log::notice(sprintf('Cache flush requested from %s', request()->ip()));
                $logged = true;
            }
            if (!isset($flushedKeys[$key])) {
                CacheFacade::forget($key);
                $flushedKeys[$key] = true;  // avoid flushing the same key twice in a request
            }
        }

        return CacheFacade::remember($key, self::LIFETIME, $callback);
    }

    /**
     * A flush is requested either by a desktop hard reload (browsers send
     * `Cache-Control: no-cache` on Ctrl+F5, which mobile browsers can't) or by
     * the `refresh-cache` query param behind the footer "Refresh data" button.
     * The param doubles as a cache-buster so the browser's own HTTP cache
     * (max_age from getCacheMiddleware) is bypassed for the page and its
     * lazy-loaded Mapillary/Mangrove fragments alike.
     */
    public static function flushRequested(): bool
    {
        return request()->header('cache-control') === 'no-cache'
            || request()->has('refresh-cache');
    }
}
