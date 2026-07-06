<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Log;

class Cache
{
    const LIFETIME = 86400;
    const SHORT_LIFETIME = 300;

    /**
     * Oldest storedAt timestamp of the cache entries read during this request,
     * i.e. how stale the most stale data on the page is. Shown in the footer.
     */
    private static ?int $oldestStoredAt = null;

    public static function getCacheMiddleware(int $lifetime = self::LIFETIME)
    {
        return 'cache.headers:public;max_age=' . $lifetime;
    }

    public static function remember(string $key, \Closure $callback, int $lifetime = self::LIFETIME): mixed
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

        $entry = CacheFacade::remember($key, $lifetime, function () use ($callback) {
            return ['storedAt' => time(), 'value' => $callback()];
        });

        // Entries written before values were wrapped with storedAt.
        if (!is_array($entry) || !array_key_exists('storedAt', $entry) || !array_key_exists('value', $entry)) {
            return $entry;
        }

        self::$oldestStoredAt = min(self::$oldestStoredAt ?? PHP_INT_MAX, $entry['storedAt']);

        return $entry['value'];
    }

    public static function getOldestStoredAt(): ?int
    {
        return self::$oldestStoredAt;
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
