<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\PageController;
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
     * unchanged content answers 304, flushed content a fresh 200.
     */
    public function testPagesRevalidateWithEtag(): void
    {
        $response = $this->get('/nefas-silk');
        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'no-cache, public');
        $etag = $response->headers->get('ETag');
        $this->assertNotNull($etag);

        $revalidation = $this->get('/nefas-silk', ['If-None-Match' => $etag]);
        $revalidation->assertStatus(304);
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
