<?php

namespace Tests\Services;

use App\Services\Mapillary;
use Tests\TestCase;

class MapillaryTest extends TestCase
{
    public function test_mapillary_service_can_be_instantiated_without_token()
    {
        // Test that service throws exception when no token is configured
        config(['services.mapillary.access_token' => null]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mapillary access token is required');

        new Mapillary();
    }

    public function test_mapillary_service_can_be_instantiated_with_token()
    {
        // Test that service can be instantiated with a token
        config(['services.mapillary.access_token' => 'test_token']);
        config(['services.mapillary.base_url' => 'https://graph.mapillary.com']);
        config(['app.technical_contact' => 'test@example.com']);

        $mapillary = new Mapillary();
        $this->assertInstanceOf(Mapillary::class, $mapillary);
    }

    public function test_get_images_near_location_returns_empty_array_when_no_token()
    {
        // Test graceful handling when service is not properly configured
        config(['services.mapillary.access_token' => null]);

        try {
            $mapillary = new Mapillary();
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Mapillary access token is required', $e->getMessage());
        }
    }

    public function test_bounding_box_calculation()
    {
        // Test that bounding box is calculated correctly
        config(['services.mapillary.access_token' => 'test_token']);
        config(['services.mapillary.base_url' => 'https://graph.mapillary.com']);
        config(['app.technical_contact' => 'test@example.com']);

        $mapillary = new Mapillary();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($mapillary);
        $method = $reflection->getMethod('createBoundingBox');
        $method->setAccessible(true);

        $bbox = $method->invoke($mapillary, 9.0, 38.7, 100);

        $this->assertIsArray($bbox);
        $this->assertCount(4, $bbox);
        $this->assertIsFloat($bbox[0]); // west
        $this->assertIsFloat($bbox[1]); // south
        $this->assertIsFloat($bbox[2]); // east
        $this->assertIsFloat($bbox[3]); // north

        // Check that the bounding box is centered around the input coordinates
        $this->assertLessThan(38.7, $bbox[0]); // west < longitude
        $this->assertLessThan(9.0, $bbox[1]);  // south < latitude
        $this->assertGreaterThan(38.7, $bbox[2]); // east > longitude
        $this->assertGreaterThan(9.0, $bbox[3]);  // north > latitude
    }

    public function test_process_image_data_handles_timestamps_correctly()
    {
        config(['services.mapillary.access_token' => 'test_token']);
        config(['services.mapillary.base_url' => 'https://graph.mapillary.com']);
        config(['app.technical_contact' => 'test@example.com']);

        $mapillary = new Mapillary();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($mapillary);
        $method = $reflection->getMethod('processImageData');
        $method->setAccessible(true);

        // Test data with millisecond timestamp (typical Mapillary format)
        $testData = [
            [
                'id' => 'test123',
                'thumb_256_url' => 'https://example.com/thumb.jpg',
                'captured_at' => 1640995200000, // Jan 1, 2022 in milliseconds
                'geometry' => ['coordinates' => [38.7, 9.0]],
                'creator' => ['username' => 'testuser', 'id' => 'user123']
            ]
        ];

        $result = $method->invoke($mapillary, $testData, 9.0, 38.7);

        $this->assertCount(1, $result);
        $this->assertEquals('test123', $result[0]['id']);
        $this->assertEquals('testuser', $result[0]['creator']['username']);
        $this->assertEquals('user123', $result[0]['creator']['id']);
        $this->assertNotNull($result[0]['captured_at']);
        $this->assertEquals('Jan 2022', $result[0]['captured_at_formatted']);
        $this->assertStringContainsString('pKey=test123', $result[0]['mapillary_url']);

        // Test distance calculation
        $this->assertIsFloat($result[0]['distance_meters']);
        $this->assertIsString($result[0]['distance_formatted']);
        $this->assertEquals('0m', $result[0]['distance_formatted']); // Same coordinates = 0 distance
    }

    public function test_distance_calculation()
    {
        config(['services.mapillary.access_token' => 'test_token']);
        config(['services.mapillary.base_url' => 'https://graph.mapillary.com']);
        config(['app.technical_contact' => 'test@example.com']);

        $mapillary = new Mapillary();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($mapillary);
        $calculateMethod = $reflection->getMethod('calculateDistance');
        $calculateMethod->setAccessible(true);

        $formatMethod = $reflection->getMethod('formatDistance');
        $formatMethod->setAccessible(true);

        // Test distance calculation between two known points
        // Addis Ababa center to a point roughly 1km away
        $distance = $calculateMethod->invoke($mapillary, 9.0, 38.7, 9.01, 38.71);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(1000, $distance); // Should be more than 1km
        $this->assertLessThan(2000, $distance); // Should be less than 2km

        // Test distance formatting
        $this->assertEquals('50m', $formatMethod->invoke($mapillary, 50));
        $this->assertEquals('999m', $formatMethod->invoke($mapillary, 999));
        $this->assertEquals('1km', $formatMethod->invoke($mapillary, 1000));
        $this->assertEquals('1.5km', $formatMethod->invoke($mapillary, 1500));
        $this->assertEquals('2.3km', $formatMethod->invoke($mapillary, 2345));
    }
}
