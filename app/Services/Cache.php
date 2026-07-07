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

    /**
     * HTTP caching for pages: any cache (browser or shared) may store the
     * response but must revalidate it on every use. While the server-side
     * data cache is unchanged that revalidation is a cheap 304 (the ETag is
     * derived from the rendered content, which only changes when the data
     * does); once anyone flushes it via the "Refresh data" button, every
     * other user picks up the fresh content on their next page load instead
     * of after a fixed max-age. The upstream APIs stay protected by the
     * server-side data cache (LIFETIME / SHORT_LIFETIME) either way.
     */
    public static function getCacheMiddleware(): string
    {
        return 'cache.headers:public;no_cache;etag';
    }

    /**
     * HTTP caching for the lazy-loaded fragments: unlike the pages they can
     * be cached without revalidating, because their URLs are versioned with
     * the embedding page's data version (lazyload.js appends `v`, see
     * remember() for how the server honours it). When the page's data
     * refreshes - via the "Refresh data" button or natural expiry - its
     * content changes, every user's next page revalidation delivers new
     * fragment URLs, and the old cached fragments simply fall out of use.
     */
    public static function getFragmentCacheMiddleware(): string
    {
        return 'cache.headers:public;max_age=' . self::LIFETIME;
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

        $wrap = function () use ($callback) {
            return ['storedAt' => time(), 'value' => $callback()];
        };

        $entry = CacheFacade::remember($key, $lifetime, $wrap);

        // Entries written before values were wrapped with storedAt.
        if (!is_array($entry) || !array_key_exists('storedAt', $entry) || !array_key_exists('value', $entry)) {
            return $entry;
        }

        // Fragment requests carry the data version of the page that embedded
        // them (`v`, appended by lazyload.js from the page's oldest storedAt):
        // data older than that page must be refetched, so a fragment is never
        // staler than the page claiming it - this is also what propagates a
        // "Refresh data" flush to the fragments' own cache keys.
        if ($entry['storedAt'] < self::requestedDataVersion() && !isset($flushedKeys[$key])) {
            CacheFacade::forget($key);
            $flushedKeys[$key] = true;
            $entry = CacheFacade::remember($key, $lifetime, $wrap);
        }

        self::$oldestStoredAt = min(self::$oldestStoredAt ?? PHP_INT_MAX, $entry['storedAt']);

        return $entry['value'];
    }

    /**
     * The minimum data freshness requested via the `v` query param (a unix
     * timestamp), clamped to the present so a forged future version cannot
     * bust the cache on every request. 0 when absent, i.e. no requirement.
     */
    private static function requestedDataVersion(): int
    {
        return min((int) request()->query('v'), time());
    }

    public static function getOldestStoredAt(): ?int
    {
        return self::$oldestStoredAt;
    }

    /**
     * A flush is requested either by a desktop hard reload (browsers send
     * `Cache-Control: no-cache` on Ctrl+F5, which mobile browsers can't) or by
     * the `refresh-cache` query param behind the footer "Refresh data" button.
     * The param's unique value also guarantees the flushing visitor an
     * unconditional fresh page response (no stored entry, so no 304). The
     * flush only reaches the keys this page render touches; the fragments'
     * own keys follow via the data version their new URLs carry (see
     * remember()), and everyone else gets the refreshed data when their
     * cached page copy next revalidates (see getCacheMiddleware).
     */
    public static function flushRequested(): bool
    {
        return request()->header('cache-control') === 'no-cache'
            || request()->has('refresh-cache');
    }
}
