<?php

namespace App\Helpers;

class GeoHelper
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @param string $unit Unit of measurement ('m' for meters, 'km' for kilometers)
     * @return float Distance in specified unit
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'm'): float
    {
        // Earth's radius
        $earthRadius = $unit === 'km' ? 6371 : 6371000; // km or meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance in meters between two coordinates
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public static function calculateDistanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return self::calculateDistance($lat1, $lon1, $lat2, $lon2, 'm');
    }

    /**
     * Calculate distance in kilometers between two coordinates
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    public static function calculateDistanceInKilometers(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return self::calculateDistance($lat1, $lon1, $lat2, $lon2, 'km');
    }

    /**
     * Format distance for display
     *
     * @param float $meters Distance in meters
     * @return string Formatted distance string
     */
    public static function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . 'm';
        } else {
            return round($meters / 1000, 1) . 'km';
        }
    }

    /**
     * Format distance in kilometers for display
     *
     * @param float $kilometers Distance in kilometers
     * @return string Formatted distance string
     */
    public static function formatDistanceFromKilometers(float $kilometers): string
    {
        if ($kilometers < 1) {
            return round($kilometers * 1000) . 'm';
        }
        return round($kilometers, 1) . 'km';
    }

    /**
     * Create a bounding box around coordinates
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param float $radius Radius in meters
     * @return array [west, south, east, north]
     */
    public static function createBoundingBox(float $lat, float $lon, float $radius): array
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
     * Check if a point is within a certain distance of another point
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @param float $maxDistance Maximum distance in meters
     * @return bool True if points are within the specified distance
     */
    public static function isWithinDistance(float $lat1, float $lon1, float $lat2, float $lon2, float $maxDistance): bool
    {
        $distance = self::calculateDistanceInMeters($lat1, $lon1, $lat2, $lon2);
        return $distance <= $maxDistance;
    }
}
