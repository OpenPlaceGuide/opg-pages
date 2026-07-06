/**
 * Lazy-loads HTML fragments (Mapillary images, Mangrove reviews) as their
 * placeholder scrolls into view. Each placeholder carries a `data-lazy-src`
 * attribute pointing at a cacheable server endpoint that returns rendered
 * HTML, which is injected in place. This avoids firing dozens of upstream
 * API requests on initial page load for places with many branches.
 */

// When the page was opened via the footer "Refresh data" button it carries a
// `refresh-cache` param. Capture it once, before we scrub it from the address
// bar, so the same flush is propagated to the lazy fragment requests below —
// otherwise their Mapillary/Mangrove data (and the browser's HTTP cache of it)
// would stay stale.
const REFRESH_PARAM = 'refresh-cache';
const refreshCacheBuster = new URLSearchParams(window.location.search).get(REFRESH_PARAM);

function scrubRefreshParam() {
    if (refreshCacheBuster === null) return;
    const url = new URL(window.location.href);
    url.searchParams.delete(REFRESH_PARAM);
    window.history.replaceState(window.history.state, '', url.toString());
}

async function loadFragment(el) {
    const src = el.dataset.lazySrc;
    if (!src) return;

    // Mark as loading so it is never observed/loaded twice.
    delete el.dataset.lazySrc;

    // Propagate the cache flush to the fragment endpoint. The unique value also
    // busts the browser's own HTTP cache of the fragment (see routes/web.php).
    const url = new URL(src, window.location.origin);
    if (refreshCacheBuster !== null) {
        url.searchParams.set(REFRESH_PARAM, refreshCacheBuster);
    }

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'fetch' },
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        el.innerHTML = await response.text();
    } catch (error) {
        // Fail silently: an unavailable third-party service must not break
        // the page. Leave the placeholder empty.
        el.innerHTML = '';
        console.warn('Lazy fragment failed to load:', url, error);
    }

    // Collapse any reserved height (used to avoid layout shift while the
    // images load) when the fragment turned out to have no visible content,
    // so branches without images/reviews don't leave a large empty gap.
    if (el.children.length === 0) {
        el.style.minHeight = '0';
    }
}

function initLazyFragments() {
    // Tidy the address bar so the cache-buster isn't bookmarked or shared;
    // done after refreshCacheBuster is captured above, and before any fetch.
    scrubRefreshParam();

    const targets = document.querySelectorAll('[data-lazy-src]');
    if (!targets.length) return;

    if (!('IntersectionObserver' in window)) {
        // Fallback: no observer support, just load everything immediately.
        targets.forEach(loadFragment);
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            obs.unobserve(entry.target);
            loadFragment(entry.target);
        });
    }, {
        // Start loading a bit before the placeholder is actually visible.
        rootMargin: '300px 0px',
    });

    targets.forEach((el) => observer.observe(el));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLazyFragments);
} else {
    initLazyFragments();
}
