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
