<?php

namespace App\Services;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class Mapillary
{
    private string $accessToken;
    private string $baseUrl;

    public function __construct()
    {
        $accessToken = config('services.mapillary.access_token');
        $baseUrl = config('services.mapillary.base_url');

        if (empty($accessToken)) {
            throw new \InvalidArgumentException('Mapillary access token is required. Please set MAPILLARY_ACCESS_TOKEN in your environment file.');
        }

        $this->accessToken = $accessToken;
        $this->baseUrl = $baseUrl ?? 'https://graph.mapillary.com';
    }

    /**
     * Fetch images near a specific location
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param float $radius Radius in meters (default: 100)
     * @param int $limit Maximum number of images to return (default: 10)
     * @return array Array of image data
     * @throws GuzzleException
     */
    public function getImagesNearLocation(float $lat, float $lon, float $radius = 100, int $limit = 10): array
    {
        // Create bounding box around the coordinates
        $bbox = $this->createBoundingBox($lat, $lon, $radius);

        $cacheKey = sprintf('mapillary_images_%s_%s_%s_%s', $lat, $lon, $radius, $limit);

        return Cache::remember($cacheKey, function () use ($bbox, $limit, $lat, $lon) {
            return $this->fetchImages($bbox, $limit, $lat, $lon);
        });
    }

    /**
     * Create a bounding box around coordinates
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param float $radius Radius in meters
     * @return array [west, south, east, north]
     */
    private function createBoundingBox(float $lat, float $lon, float $radius): array
    {
        // Convert radius from meters to degrees (approximate)
        $latDelta = $radius / 111000; // 1 degree latitude ≈ 111km
        $lonDelta = $radius / (111000 * cos(deg2rad($lat))); // Adjust for longitude

        return [
            $lon - $lonDelta, // west
            $lat - $latDelta, // south
            $lon + $lonDelta, // east
            $lat + $latDelta  // north
        ];
    }

    /**
     * Fetch images from Mapillary API
     *
     * @param array $bbox Bounding box [west, south, east, north]
     * @param int $limit Maximum number of images
     * @param float $centerLat Center latitude for distance calculation
     * @param float $centerLon Center longitude for distance calculation
     * @return array
     * @throws GuzzleException
     */
    private function fetchImages(array $bbox, int $limit, float $centerLat, float $centerLon): array
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'OAuth ' . $this->accessToken,
                'User-Agent' => $this->buildUserAgent()
            ]
        ]);

        $bboxString = implode(',', $bbox);

        $requestStart = microtime(true);

        try {
            $response = $client->get('/images', [
                'query' => [
                    'bbox' => $bboxString,
                    'limit' => $limit,
                    'fields' => 'id,thumb_256_url,thumb_1024_url,captured_at,compass_angle,geometry,creator,quality_score'
                ]
            ]);
        } catch (ClientException $e) {
            Log::error(sprintf('Mapillary API error, time %fs: %s', microtime(true) - $requestStart, $e->getMessage()));
            return [];
        }

        $requestTime = microtime(true) - $requestStart;
        Log::notice(sprintf('Mapillary request took %fs', $requestTime));

        $data = json_decode($response->getBody(), true);

        if (!isset($data['data'])) {
            Log::warning('Mapillary API returned unexpected response format');
            return [];
        }

        return $this->processImageData($data['data'], $centerLat, $centerLon);
    }

    /**
     * Process and format image data from API response
     *
     * @param array $images Raw image data from API
     * @param float $centerLat Center latitude for distance calculation
     * @param float $centerLon Center longitude for distance calculation
     * @return array Processed image data
     */
    private function processImageData(array $images, float $centerLat, float $centerLon): array
    {
        $processed = [];

        foreach ($images as $image) {
            // Parse captured_at timestamp properly
            $capturedAt = null;
            if (isset($image['captured_at'])) {
                // Mapillary timestamps are in milliseconds, convert to seconds
                $timestamp = is_numeric($image['captured_at']) ?
                    intval($image['captured_at']) / 1000 :
                    strtotime($image['captured_at']);
                $capturedAt = $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : null;
            }

            // Calculate distance from center point
            $imageLat = $image['geometry']['coordinates'][1] ?? null;
            $imageLon = $image['geometry']['coordinates'][0] ?? null;
            $distance = null;
            $distanceFormatted = null;

            if ($imageLat !== null && $imageLon !== null) {
                $distance = $this->calculateDistance($centerLat, $centerLon, $imageLat, $imageLon);
                $distanceFormatted = $this->formatDistance($distance);
            }

            $processed[] = [
                'id' => $image['id'],
                'thumbnail_url' => $image['thumb_256_url'] ?? null,
                'large_thumbnail_url' => $image['thumb_1024_url'] ?? null,
                'captured_at' => $capturedAt,
                'captured_at_formatted' => $capturedAt ? date('M Y', strtotime($capturedAt)) : null,
                'compass_angle' => $image['compass_angle'] ?? null,
                'coordinates' => [
                    'lat' => $imageLat,
                    'lon' => $imageLon
                ],
                'distance_meters' => $distance,
                'distance_formatted' => $distanceFormatted,
                'creator' => [
                    'username' => $image['creator']['username'] ?? null,
                    'id' => $image['creator']['id'] ?? null
                ],
                'quality_score' => $image['quality_score'] ?? null,
                'mapillary_url' => sprintf('https://www.mapillary.com/app/?pKey=%s', $image['id']),
                'attribution' => 'Mapillary'
            ];
        }

        // Sort by quality score (highest first), then by distance (closest first)
        usort($processed, function($a, $b) {
            // First sort by quality score (higher is better)
            $qualityA = $a['quality_score'] ?? 0;
            $qualityB = $b['quality_score'] ?? 0;

            if ($qualityA !== $qualityB) {
                return $qualityB <=> $qualityA; // Descending order (higher quality first)
            }

            // If quality scores are equal, sort by distance (closer is better)
            $distanceA = $a['distance_meters'] ?? PHP_FLOAT_MAX;
            $distanceB = $b['distance_meters'] ?? PHP_FLOAT_MAX;

            return $distanceA <=> $distanceB; // Ascending order (closer first)
        });

        return $processed;
    }

    /**
     * Get the best quality images for an area using a larger search radius
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param float $radius Radius in meters (default: 5000 for 5km)
     * @param int $limit Maximum number of images to return (default: 5)
     * @return array Array of best quality image data
     * @throws GuzzleException
     */
    public function getBestImagesForArea(float $lat, float $lon, float $radius = 5000, int $limit = 5): array
    {
        // Use a larger limit to get more images to choose from, then filter to the best ones
        $searchLimit = 2500;

        $images = $this->getImagesNearLocation($lat, $lon, $radius, $searchLimit);

        // Return only the top images (already sorted by quality and distance)
        return array_slice($images, 0, $limit);
    }

    /**
     * Fetch images within a specific bounding box
     *
     * @param array $boundingBox Bounding box with keys: north, south, east, west
     * @param int $limit Maximum number of images to return (default: 5)
     * @return array Array of image data
     * @throws GuzzleException
     */
    public function getImagesInBoundingBox(array $boundingBox, int $limit = 5): array
    {
        // Validate bounding box
        if (!isset($boundingBox['north'], $boundingBox['south'], $boundingBox['east'], $boundingBox['west'])) {
            throw new \InvalidArgumentException('Bounding box must contain north, south, east, and west coordinates');
        }

        // Calculate center point for distance calculations
        $centerLat = ($boundingBox['north'] + $boundingBox['south']) / 2;
        $centerLon = ($boundingBox['east'] + $boundingBox['west']) / 2;

        // Create cache key based on bounding box
        $cacheKey = sprintf('mapillary_bbox_%s_%s_%s_%s_%s',
            $boundingBox['west'], $boundingBox['south'],
            $boundingBox['east'], $boundingBox['north'], $limit);

        return Cache::remember($cacheKey, function () use ($boundingBox, $limit, $centerLat, $centerLon) {
            // Use a larger search limit to get more images to choose from
            $searchLimit = min($limit * 10, 500);
            return $this->fetchImagesFromBoundingBox($boundingBox, $searchLimit, $centerLat, $centerLon, $limit);
        });
    }

    /**
     * Build user agent string for API requests
     *
     * @return string
     */
    private function buildUserAgent(): string
    {
        $contact = config('app.technical_contact');
        $version = 'dev'; // FIXME: detect proper version

        if (empty($contact)) {
            throw new \InvalidArgumentException('Please configure APP_TECHNICAL_CONTACT in your environment file. This will be used to identify external requests');
        }

        return sprintf('opg-pages/%s (%s, %s)', $version, url(''), $contact);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Format distance for display
     *
     * @param float $meters Distance in meters
     * @return string Formatted distance string
     */
    private function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . 'm';
        } else {
            return round($meters / 1000, 1) . 'km';
        }
    }

    /**
     * Fetch images from Mapillary API using a bounding box
     *
     * @param array $boundingBox Bounding box [west, south, east, north]
     * @param int $searchLimit Maximum number of images to search through
     * @param float $centerLat Center latitude for distance calculation
     * @param float $centerLon Center longitude for distance calculation
     * @param int $finalLimit Final number of images to return
     * @return array
     * @throws GuzzleException
     */
    private function fetchImagesFromBoundingBox(array $boundingBox, int $searchLimit, float $centerLat, float $centerLon, int $finalLimit): array
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'OAuth ' . $this->accessToken,
                'User-Agent' => $this->buildUserAgent()
            ]
        ]);

        // Format bounding box as west,south,east,north
        $bboxString = sprintf('%f,%f,%f,%f',
            $boundingBox['west'], $boundingBox['south'],
            $boundingBox['east'], $boundingBox['north']);

        $requestStart = microtime(true);

        try {
            $response = $client->get('/images', [
                'query' => [
                    'bbox' => $bboxString,
                    'limit' => $searchLimit,
                    'fields' => 'id,thumb_256_url,thumb_1024_url,captured_at,compass_angle,geometry,creator,quality_score'
                ]
            ]);
        } catch (ClientException $e) {
            Log::error(sprintf('Mapillary API error, time %fs: %s', microtime(true) - $requestStart, $e->getMessage()));
            return [];
        }

        $requestTime = microtime(true) - $requestStart;
        Log::notice(sprintf('Mapillary bounding box request took %fs', $requestTime));

        $data = json_decode($response->getBody(), true);

        if (!isset($data['data'])) {
            Log::warning('Mapillary API returned unexpected response format');
            return [];
        }

        $processedImages = $this->processImageData($data['data'], $centerLat, $centerLon);

        // Return only the top images (already sorted by quality and distance)
        return array_slice($processedImages, 0, $finalLimit);
    }
}
