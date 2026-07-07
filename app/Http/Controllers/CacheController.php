<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Handles the footer "Refresh data" button. It is a POST endpoint (not a
 * crawlable link) so search engine bots never trigger cache flushes.
 *
 * The flush itself happens lazily during the redirected page render: the
 * `refresh-cache` query param makes App\Services\Cache::remember() forget the
 * keys the page (and its lazy Mapillary/Mangrove fragments) touch. Because the
 * data cache is shared, the flush reaches every user: their stored copies
 * revalidate on the next load and pick up the fresh content (see
 * App\Services\Cache::getCacheMiddleware). The param's unique value gives the
 * flushing visitor an unconditional fresh response right away.
 */
class CacheController extends Controller
{
    public function refresh(Request $request): RedirectResponse
    {
        // Only trust the path portion of the submitted return URL, so this
        // can never be used as an open redirect to another host.
        $path = parse_url((string) $request->input('return'), PHP_URL_PATH) ?: '/';
        $path = Str::start($path, '/');

        $target = $path . '?refresh-cache=' . now()->getTimestampMs();

        return redirect()->to($target);
    }
}
