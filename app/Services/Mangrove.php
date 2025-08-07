<?php

namespace App\Services;

use App\Helpers\GeoHelper;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class Mangrove
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.mangrove.reviews';
    }

    /**
     * Fetch reviews for Ethiopia using the bounding box
     *
     * @return array Array of review data
     * @throws GuzzleException
     */
    public function getReviewsForEthiopia(): array
    {
        $cacheKey = 'mangrove_reviews_ethiopia';

        return Cache::remember($cacheKey, function () {
            return $this->fetchReviews();
        });
    }

    /**
     * Fetch reviews from Mangrove API for Ethiopia
     *
     * @return array
     * @throws GuzzleException
     */
    private function fetchReviews(): array
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'User-Agent' => $this->buildUserAgent()
            ]
        ]);

        $requestStart = microtime(true);

        try {
            $response = $client->get('/geo', [
                'query' => [
                    'xmin' => 33.0,
                    'ymin' => 3.4,
                    'xmax' => 48.0,
                    'ymax' => 15.1
                ]
            ]);
        } catch (ClientException $e) {
            Log::error(sprintf('Mangrove API error, time %fs: %s', microtime(true) - $requestStart, $e->getMessage()));
            return [];
        }

        $requestTime = microtime(true) - $requestStart;
        Log::notice(sprintf('Mangrove request took %fs', $requestTime));

        $data = json_decode($response->getBody(), true);

        if (!isset($data['reviews'])) {
            Log::warning('Mangrove API returned unexpected response format');
            return [];
        }

        return $this->processReviewData($data['reviews']);
    }

    /**
     * Process raw review data from the API
     *
     * @param array $reviews
     * @return array
     */
    private function processReviewData(array $reviews): array
    {
        $processedReviews = [];

        foreach ($reviews as $review) {
            if (!isset($review['payload'])) {
                continue;
            }

            $payload = $review['payload'];
            $geo = $review['geo'] ?? null;

            if (!$geo || !isset($geo['coordinates'])) {
                continue;
            }

            $processedReview = [
                'id' => $review['signature'] ?? uniqid(),
                'rating' => $payload['rating'] ?? 0,
                'opinion' => $payload['opinion'] ?? '',
                'latitude' => $geo['coordinates']['y'],
                'longitude' => $geo['coordinates']['x'],
                'uncertainty' => $geo['uncertainty'] ?? 30,
                'created_at' => isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : null,
                'created_at_formatted' => isset($payload['iat']) ? date('M j, Y', $payload['iat']) : null,
                'images' => [],
                'metadata' => $payload['metadata'] ?? [],
                'subject' => $this->parseSubject($payload['sub'] ?? ''),
            ];

            // Process images if they exist
            if (isset($payload['images']) && is_array($payload['images'])) {
                foreach ($payload['images'] as $image) {
                    if (isset($image['src'])) {
                        $processedReview['images'][] = [
                            'url' => $image['src'],
                            'alt' => 'Review image'
                        ];
                    }
                }
            }

            // Convert rating from 0-100 to 0-5 stars
            $processedReview['star_rating'] = round(($processedReview['rating'] / 100) * 5, 1);

            $processedReviews[] = $processedReview;
        }

        return $processedReviews;
    }

    /**
     * Parse the subject field to extract location name and coordinates
     *
     * @param string $subject
     * @return array
     */
    private function parseSubject(string $subject): array
    {
        $parsed = [
            'name' => '',
            'coordinates' => null
        ];

        // Subject format: "geo:lat,lon?q=Name&u=uncertainty"
        if (preg_match('/geo:([0-9.-]+),([0-9.-]+)\?q=([^&]+)/', $subject, $matches)) {
            $parsed['coordinates'] = [
                'lat' => (float) $matches[1],
                'lon' => (float) $matches[2]
            ];
            $parsed['name'] = urldecode($matches[3]);
        }

        return $parsed;
    }

    /**
     * Find reviews near a specific location
     *
     * @param float $lat
     * @param float $lon
     * @param float $radiusKm
     * @return array
     */
    public function getReviewsNearLocation(float $lat, float $lon, float $radiusKm = 1.0): array
    {
        $allReviews = $this->getReviewsForEthiopia();
        $nearbyReviews = [];

        foreach ($allReviews as $review) {
            $distance = GeoHelper::calculateDistanceInKilometers(
                $lat, $lon,
                $review['latitude'], $review['longitude']
            );

            if ($distance <= $radiusKm) {
                $review['distance_km'] = round($distance, 2);
                $review['distance_formatted'] = GeoHelper::formatDistanceFromKilometers($distance);
                $nearbyReviews[] = $review;
            }
        }

        // Sort by distance
        usort($nearbyReviews, function($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });

        return $nearbyReviews;
    }

    /**
     * Find reviews near a specific location within meters
     *
     * @param float $lat
     * @param float $lon
     * @param float $radiusMeters
     * @return array
     */
    public function getReviewsNearLocationMeters(float $lat, float $lon, float $radiusMeters = 100.0): array
    {
        $allReviews = $this->getReviewsForEthiopia();
        $nearbyReviews = [];

        foreach ($allReviews as $review) {
            $distanceMeters = GeoHelper::calculateDistanceInMeters(
                $lat, $lon,
                $review['latitude'], $review['longitude']
            );

            if ($distanceMeters <= $radiusMeters) {
                $review['distance_meters'] = round($distanceMeters, 1);
                $review['distance_formatted'] = GeoHelper::formatDistance($distanceMeters);
                $nearbyReviews[] = $review;
            }
        }

        // Sort by distance
        usort($nearbyReviews, function($a, $b) {
            return $a['distance_meters'] <=> $b['distance_meters'];
        });

        return $nearbyReviews;
    }

    /**
     * Find reviews that mention a company name globally
     *
     * @param string $companyName
     * @return array
     */
    public function getReviewsByCompanyName(string $companyName): array
    {
        $allReviews = $this->getReviewsForEthiopia();
        $companyReviews = [];

        $searchTerms = [
            strtolower($companyName),
            strtolower(trim($companyName)),
        ];

        foreach ($allReviews as $review) {
            $found = false;

            // Search in subject name
            if (isset($review['subject']['name'])) {
                $subjectName = strtolower($review['subject']['name']);
                foreach ($searchTerms as $term) {
                    if (strpos($subjectName, $term) !== false) {
                        $review['match_type'] = 'company_name';
                        $review['match_source'] = 'subject';
                        $companyReviews[] = $review;
                        $found = true;
                        break;
                    }
                }
            }

            // Search in review opinion text if not already found
            if (!$found && !empty($review['opinion'])) {
                $opinion = strtolower($review['opinion']);
                foreach ($searchTerms as $term) {
                    if (strpos($opinion, $term) !== false) {
                        $review['match_type'] = 'company_mention';
                        $review['match_source'] = 'opinion';
                        $companyReviews[] = $review;
                        break;
                    }
                }
            }
        }

        return $companyReviews;
    }

    /**
     * Get combined reviews for a business: both location-based and company-wide
     *
     * @param array $branches Array of branch locations
     * @param string $companyName Company name to search for
     * @return array Combined and deduplicated reviews
     */
    public function getCombinedBusinessReviews(array $branches, string $companyName): array
    {
        $allReviews = [];
        $reviewIds = [];

        // Get location-based reviews for each branch
        foreach ($branches as $branch) {
            if (isset($branch->lat) && isset($branch->lon)) {
                $locationReviews = $this->getReviewsNearLocationMeters($branch->lat, $branch->lon, 3.0);

                foreach ($locationReviews as $review) {
                    if (!isset($reviewIds[$review['id']])) {
                        $review['match_type'] = 'location';
                        $review['branch_lat'] = $branch->lat;
                        $review['branch_lon'] = $branch->lon;
                        $allReviews[] = $review;
                        $reviewIds[$review['id']] = true;
                    }
                }
            }
        }

        // Get company-wide reviews
        $companyReviews = $this->getReviewsByCompanyName($companyName);
        foreach ($companyReviews as $review) {
            if (!isset($reviewIds[$review['id']])) {
                $allReviews[] = $review;
                $reviewIds[$review['id']] = true;
            }
        }

        // Sort by rating (highest first) then by date (newest first)
        usort($allReviews, function($a, $b) {
            if ($a['star_rating'] !== $b['star_rating']) {
                return $b['star_rating'] <=> $a['star_rating'];
            }
            return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
        });

        return $allReviews;
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
}
