<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // The footer "Refresh data" form lives in publicly cached pages, so a
        // shared cache/CDN would serve one visitor's session token to everyone
        // else and the POST would 419. The action is harmless (it only
        // redirects with a cache-busting param), so skip CSRF instead of
        // embedding a session token in cacheable HTML.
        'refresh-cache',
    ];
}
