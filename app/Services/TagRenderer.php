<?php

namespace App\Services;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use stdClass;
use Ujamii\OsmOpeningHours\OsmStringToOpeningHoursConverter;

class TagRenderer
{
    public function __construct(private readonly stdClass $tags)
    {
    }


    public static function tagListToObject($array)
    {
        $keyedArray = [];
        foreach($array as $tag) {
            $keyedArray[$tag['key']] = $tag['value'];
        }
        return (object)$keyedArray;
    }

    /**
     * OSM website/contact:website values are user-controlled, so rendering
     * them into an href unfiltered would allow javascript:/data: links.
     * Allow only http(s); scheme-less values (common in OSM) default to https.
     */
    public static function safeWebsiteUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            return $url;
        }
        if ($scheme !== '') {
            return null;
        }

        return 'https://' . ltrim($url, '/');
    }

    /**
     * Build a profile link for a social-media contact tag. OSM values are
     * either a bare username (the common case in Ethiopia, e.g. "_yenuyabi")
     * or a full profile URL. Returns a safe http(s) URL, or null if empty.
     */
    public static function socialUrl(string $platform, ?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Already a URL, or a scheme-less path/domain (e.g. "tiktok.com/@x"):
        // reuse the website sanitiser, which rejects javascript:/data: links
        // and defaults scheme-less values to https.
        if (preg_match('#^https?://#i', $value) || str_contains($value, '/')) {
            return self::safeWebsiteUrl($value);
        }

        $handle = ltrim($value, '@');
        if ($handle === '') {
            return null;
        }

        return match ($platform) {
            'tiktok' => 'https://www.tiktok.com/@' . rawurlencode($handle),
            'instagram' => 'https://www.instagram.com/' . rawurlencode($handle),
            'telegram' => 'https://t.me/' . rawurlencode($handle),
            default => null,
        };
    }

    // phone: as is
    // atm=yes taginfo
    // name: print
    // name:am print, too ?
    // opening_hours: opening_hours.js https://github.com/opening-hours/opening_hours.js/
    // operator: print
    // website: print

    public function getTagTexts(): array
    {
        $tags = $this->tags;
        $lines = [];
        foreach ($tags as $key=>$value) {
            if ($key === 'opening_hours') {
                $key = 'Opening Times';
                $lines[] = $key . ": " . $value;
                continue;
            }

            $tagInfo = $this->queryTagInfo($key, $value);
            if ($tagInfo !== null) {
                $lines[] = $tagInfo;
            }
        }

        return $lines;
    }

    private function queryTagInfo($key, $value)
    {
        $row = DB::connection('sqlite_taginfo')
            ->table('wikipages')
            ->select('description')
            ->where('lang', 'en')
            ->where('key', $key)
            ->where('value', $value)
            ->get()->first();

        if ($row === null) {
            return null;
        }

        return $row->description;
    }
}
