<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\PageController;
use App\Services\Cache;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Tests\TestCase;

class RoutesTest extends TestCase
{

    /**
     * @see PageController::page()
     */
    public function testSinglePlace(): void
    {
        $response = $this->get('/bandira');
        $response->assertStatus(200);
    }

    /**
     * @see PageController::page()
     */
    public function testMultiPlace(): void
    {
        $response = $this->get('/zemen-bank');
        $response->assertStatus(200);
    }

    public function testTypePageOverview(): void
    {
        $response = $this->get('/nefas-silk/businesses');
        $response->assertStatus(200);
    }
    public function testNewsfeedPage(): void
    {
        $response = $this->get('/nefas-silk/newsfeed');
        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'no-cache, public');
    }

    /**
     * Pages must revalidate on every use instead of being served stale from
     * HTTP caches, so a "Refresh data" flush reaches other users too (#65):
     * unchanged content answers 304, flushed content a fresh 200. The page
     * also embeds its data version, which versions the fragment URLs.
     */
    public function testPagesRevalidateWithEtag(): void
    {
        $response = $this->get('/nefas-silk');
        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'no-cache, public');
        $response->assertSee('name="opg-data-version"', false);
        $etag = $response->headers->get('ETag');
        $this->assertNotNull($etag);

        $revalidation = $this->get('/nefas-silk', ['If-None-Match' => $etag]);
        $revalidation->assertStatus(304);
    }

    /**
     * Fragments are cached without revalidation - their freshness comes from
     * the page data version (`v`) in their URLs instead (see
     * Cache::getFragmentCacheMiddleware).
     */
    public function testFragmentsAreCachedWithMaxAge(): void
    {
        $response = $this->get('/api/branch/8.9901018/38.7845284/mapillary');
        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    /**
     * The `v` query param is a minimum-freshness requirement: cached data
     * older than the page that embedded the fragment URL is refetched, so a
     * "Refresh data" flush reaches the fragments' own cache keys too (#65).
     */
    public function testDataVersionRefetchesOlderEntries(): void
    {
        CacheFacade::put('routes-test-version', ['storedAt' => time() - 100, 'value' => 'stale'], 60);

        request()->query->set('v', (string) time());
        $value = Cache::remember('routes-test-version', fn () => 'fresh', 60);

        $this->assertSame('fresh', $value);
        CacheFacade::forget('routes-test-version');
    }

    public function testSitemapContainsNewsfeeds(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
        $response->assertSee('/nefas-silk/newsfeed</loc>', false);
        $response->assertSee('<changefreq>always</changefreq>', false);
    }

    public function testAreaPage(): void
    {
        $response = $this->get('/nefas-silk');
        $response->assertStatus(200);
    }

    public function testOsmPageWithSlug(): void
    {
        $response = $this->get('/n6700600973/awash-international-bank-mekenisa-abo-branch');
        $response->assertStatus(200);
    }

    public function testOsmPageWithoutSlug(): void
    {
        $response = $this->get('/n6700600973');
        $response->assertStatus(200);
    }

    public function testTripleZoom(): void
    {
        $response = $this->get('/assets/static-map/8.9901018/38.7845284/Zemen%20Bank.png');
        $response->assertStatus(200);
    }
}
