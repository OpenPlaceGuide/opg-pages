<?php

namespace App\Http\Controllers;

use App\Services\Mangrove;
use App\Services\Mapillary;
use Illuminate\Support\Facades\Log;

/**
 * Serves per-branch Mapillary images and Mangrove reviews as small HTML
 * fragments that are lazy-loaded by the browser when a branch scrolls into
 * view. This keeps the main place page from making dozens of upstream API
 * calls up front. The fragment responses are cached long-term without
 * revalidation; their URLs carry the embedding page's data version, which
 * both busts the browser cache and makes the data cache refetch anything
 * older (see Cache::getFragmentCacheMiddleware and Cache::remember).
 */
class BranchDataController extends Controller
{
    private const MAPILLARY_LIMIT = 3;
    private const MAPILLARY_RADIUS_METERS = 50;
    private const MANGROVE_RADIUS_METERS = 3.0;

    public function mapillary(string $lat, string $lon)
    {
        $images = [];

        try {
            $images = (new Mapillary())->getImagesNearLocation(
                (float) $lat,
                (float) $lon,
                self::MAPILLARY_RADIUS_METERS,
                self::MAPILLARY_LIMIT
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch Mapillary images for fragment: ' . $e->getMessage());
        }

        return response()->view('fragments.mapillary', [
            'images' => $images,
        ]);
    }

    public function mangrove(string $lat, string $lon)
    {
        $reviews = [];

        try {
            $reviews = (new Mangrove())->getReviewsNearLocationMeters(
                (float) $lat,
                (float) $lon,
                self::MANGROVE_RADIUS_METERS
            );

            foreach ($reviews as &$review) {
                $review['match_type'] = 'location';
            }
            unset($review);
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch Mangrove reviews for fragment: ' . $e->getMessage());
        }

        return response()->view('fragments.mangrove', [
            'reviews' => $reviews,
        ]);
    }
}
