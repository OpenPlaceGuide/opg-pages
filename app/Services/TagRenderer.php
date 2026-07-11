<?php

namespace App\Services;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use stdClass;
use Ujamii\OsmOpeningHours\OsmStringToOpeningHoursConverter;

class TagRenderer
{
    /**
     * Supported social/contact platforms. Each entry maps the OSM tag key to the
     * display label and the profile-URL prefix used for bare handles.
     */
    private const SOCIAL_PLATFORMS = [
        'tiktok'    => ['label' => 'TikTok',    'baseUrl' => 'https://www.tiktok.com/@'],
        'instagram' => ['label' => 'Instagram', 'baseUrl' => 'https://www.instagram.com/'],
        'telegram'  => ['label' => 'Telegram',  'baseUrl' => 'https://t.me/'],
        'facebook'  => ['label' => 'Facebook',  'baseUrl' => 'https://www.facebook.com/'],
        'whatsapp'  => ['label' => 'WhatsApp',  'baseUrl' => 'https://wa.me/'],
    ];

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
     * Normalise a bare social handle. Most platforms allow a leading "@" that
     * should be stripped; WhatsApp values are phone numbers, so spaces,
     * non-digit characters and the leading "+" are removed for the wa.me link.
     */
    private static function normalizeSocialHandle(string $platform, string $handle): string
    {
        $handle = ltrim($handle, '@');
        return match ($platform) {
            'whatsapp' => preg_replace('/[^0-9]/', '', $handle),
            default => $handle,
        };
    }

    /**
     * Build a profile link for a social-media contact tag. OSM values are
     * either a bare username (the common case in Ethiopia, e.g. "_yenuyabi")
     * or a full profile URL. Returns a safe http(s) URL, or null if empty.
     */
    public static function socialUrl(string $platform, ?string $value): ?string
    {
        if (!isset(self::SOCIAL_PLATFORMS[$platform])) {
            return null;
        }

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

        $handle = self::normalizeSocialHandle($platform, $value);
        if ($handle === '') {
            return null;
        }

        return self::SOCIAL_PLATFORMS[$platform]['baseUrl'] . rawurlencode($handle);
    }

    /**
     * All phone numbers for a POI. OSM multi-value phone tags use ';' but ','
     * occurs in the wild too. Lines are often broken in Ethiopia, so callers
     * need every alternative.
     *
     * @return array<string>
     */
    public static function phones(object $tags): array
    {
        $raw = $tags->phone ?? $tags->{'contact:phone'} ?? '';
        return array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string) $raw))));
    }

    /**
     * The (sanitised) website for a POI, from either the plain or contact: tag.
     */
    public static function websiteUrl(object $tags): ?string
    {
        return self::safeWebsiteUrl($tags->website ?? $tags->{'contact:website'} ?? null);
    }

    /**
     * Business-wide social links for a set of POIs. Collects every supported
     * social platform from every POI and keeps the first of each platform, so a
     * chain shows one link per network rather than one per branch.
     *
     * @param iterable<object> $tagsList
     * @return array<string, array{url: string, label: string}> platform => metadata
     */
    public static function socialLinks(iterable $tagsList): array
    {
        $links = [];
        foreach ($tagsList as $tags) {
            foreach (array_keys(self::SOCIAL_PLATFORMS) as $platform) {
                if (isset($links[$platform])) {
                    continue;
                }
                $raw = $tags->{'contact:' . $platform} ?? $tags->{$platform} ?? null;
                $url = self::socialUrl($platform, $raw);
                if ($url !== null) {
                    $links[$platform] = [
                        'url' => $url,
                        'label' => self::SOCIAL_PLATFORMS[$platform]['label'],
                    ];
                }
            }
        }
        return $links;
    }

    /**
     * Human-readable address parts from OSM addr:* tags plus level, in reading
     * order (building name, street, unit, floor). These tags carry no taginfo
     * wiki description, so they need explicit rendering.
     *
     * @return array<string>
     */
    public static function addressParts(object $tags): array
    {
        $get = static fn (string $key): string => trim((string) ($tags->$key ?? ''));

        $parts = [];

        if (($housename = $get('addr:housename')) !== '') {
            $parts[] = $housename;
        }

        // Street line: "12 Main Street" — the house number prefixes the street.
        $street = trim($get('addr:housenumber') . ' ' . $get('addr:street'));
        if ($street !== '') {
            $parts[] = $street;
        }

        if (($unit = $get('addr:unit')) !== '') {
            $parts[] = 'Unit ' . $unit;
        }

        // addr:floor and level both describe the storey; prefer the addr namespace.
        $floor = $get('addr:floor') !== '' ? $get('addr:floor') : $get('level');
        if ($floor !== '') {
            $parts[] = 'Level ' . $floor;
        }

        return $parts;
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
