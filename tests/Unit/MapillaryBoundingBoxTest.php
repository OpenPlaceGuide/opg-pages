<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Mapillary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MapillaryBoundingBoxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set required config values for testing
        config(['services.mapillary.access_token' => 'test_token']);
        config(['app.technical_contact' => 'test@example.com']);
    }

    public function test_getImagesInBoundingBox_validates_bounding_box()
    {
        $mapillary = new Mapillary();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bounding box must contain north, south, east, and west coordinates');
        
        $mapillary->getImagesInBoundingBox(['invalid' => 'box']);
    }

    public function test_getImagesInBoundingBox_creates_correct_cache_key()
    {
        $boundingBox = [
            'north' => 40.7829,
            'south' => 40.7489,
            'east' => -73.9441,
            'west' => -73.9901
        ];
        
        // Mock the HTTP response
        Http::fake([
            'graph.mapillary.com/*' => Http::response([
                'data' => []
            ])
        ]);
        
        $mapillary = new Mapillary();
        
        // Clear cache first
        Cache::flush();
        
        // Call the method
        $result = $mapillary->getImagesInBoundingBox($boundingBox, 5);
        
        // Verify cache key was created
        $expectedCacheKey = sprintf('mapillary_bbox_%s_%s_%s_%s_%s', 
            $boundingBox['west'], $boundingBox['south'], 
            $boundingBox['east'], $boundingBox['north'], 5);
        
        $this->assertTrue(Cache::has($expectedCacheKey));
        $this->assertEquals([], $result);
    }

    public function test_getImagesInBoundingBox_method_exists_and_returns_array()
    {
        $boundingBox = [
            'north' => 40.7829,
            'south' => 40.7489,
            'east' => -73.9441,
            'west' => -73.9901
        ];

        $mapillary = new Mapillary();

        // Test that the method exists
        $this->assertTrue(method_exists($mapillary, 'getImagesInBoundingBox'));

        // Mock the HTTP response to avoid actual API calls
        Http::fake([
            'graph.mapillary.com/*' => Http::response([
                'data' => []
            ])
        ]);

        // Test that the method returns an array
        $result = $mapillary->getImagesInBoundingBox($boundingBox, 5);
        $this->assertIsArray($result);
    }
}
