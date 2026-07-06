<?php

namespace App\Services;

/**
 * Encodes latitude/longitude into an Open Location Code ("plus code"),
 * the short, offline-friendly grid reference shown next to the raw GPS
 * coordinates on place pages.
 *
 * This is a minimal, self-contained implementation of the pairs section of
 * the Open Location Code spec (https://github.com/google/open-location-code):
 * a full 10-digit code ("8FVC9G8F+6X") formatted as 8 digits, a '+' separator
 * and 2 more digits. That is the precision Google Maps and osm.org display,
 * so we don't implement the optional grid-refinement digits beyond it.
 */
class OpenLocationCode
{
    private const CODE_ALPHABET = '23456789CFGHJMPQRVWX';
    private const ENCODING_BASE = 20;
    private const LATITUDE_MAX = 90;
    private const LONGITUDE_MAX = 180;
    private const SEPARATOR_POSITION = 8;

    /**
     * Encode a coordinate into a 10-digit full plus code (e.g. "8FVC9G8F+6X").
     */
    public static function encode(float $latitude, float $longitude): string
    {
        $latitude = self::clipLatitude($latitude);
        $longitude = self::normalizeLongitude($longitude);

        // Exactly +90 has no cell above it; nudge it into the top cell.
        if ($latitude === (float) self::LATITUDE_MAX) {
            $latitude -= 0.9 * self::pairResolution(4);
        }

        // Shift into a positive range so integer division yields digit indices.
        $latValue = $latitude + self::LATITUDE_MAX;
        $lonValue = $longitude + self::LONGITUDE_MAX;

        $code = '';
        for ($pair = 0; $pair < 5; $pair++) {
            $resolution = self::pairResolution($pair);

            $latDigit = (int) floor($latValue / $resolution);
            $lonDigit = (int) floor($lonValue / $resolution);

            $code .= self::CODE_ALPHABET[$latDigit] . self::CODE_ALPHABET[$lonDigit];

            $latValue -= $latDigit * $resolution;
            $lonValue -= $lonDigit * $resolution;
        }

        return substr($code, 0, self::SEPARATOR_POSITION)
            . '+'
            . substr($code, self::SEPARATOR_POSITION);
    }

    /**
     * Degrees covered by one digit at the given pair position (0-based).
     * First pair = 20°, then divided by the encoding base each step.
     */
    private static function pairResolution(int $pair): float
    {
        return self::ENCODING_BASE / (self::ENCODING_BASE ** $pair);
    }

    private static function clipLatitude(float $latitude): float
    {
        return min(self::LATITUDE_MAX, max(-self::LATITUDE_MAX, $latitude));
    }

    private static function normalizeLongitude(float $longitude): float
    {
        while ($longitude < -self::LONGITUDE_MAX) {
            $longitude += 360;
        }
        while ($longitude >= self::LONGITUDE_MAX) {
            $longitude -= 360;
        }

        return $longitude;
    }
}
