{{--
    Emits the per-POI-type accent CSS custom properties (light + dark).
    $color is the color name from the data repository YAML (type/area color).
    Values are space-separated RGB so Tailwind's accent-* utilities can
    apply alpha. Replaces the old to-{color}-100 gradient + safelist hack.
--}}
@php
    $accentMap = [
        'green' => [
            'light' => ['accent' => '11 122 49', 'contrast' => '255 255 255', 'deep' => '5 61 24', 'soft' => '226 241 228', 'text' => '9 100 41'],
            'dark' => ['accent' => '71 179 106', 'contrast' => '15 18 12', 'deep' => '3 26 11', 'soft' => '24 42 28', 'text' => '121 209 148'],
        ],
        'blue' => [
            'light' => ['accent' => '24 100 171', 'contrast' => '255 255 255', 'deep' => '12 53 92', 'soft' => '224 236 248', 'text' => '21 86 148'],
            'dark' => ['accent' => '91 162 224', 'contrast' => '12 16 20', 'deep' => '6 22 38', 'soft' => '22 34 48', 'text' => '137 190 240'],
        ],
        'red' => [
            'light' => ['accent' => '200 58 43', 'contrast' => '255 255 255', 'deep' => '108 27 18', 'soft' => '250 229 225', 'text' => '172 48 35'],
            'dark' => ['accent' => '229 106 85', 'contrast' => '26 12 10', 'deep' => '46 12 7', 'soft' => '48 25 21', 'text' => '240 145 128'],
        ],
        'yellow' => [
            'light' => ['accent' => '240 185 43', 'contrast' => '33 30 22', 'deep' => '138 100 8', 'soft' => '251 240 210', 'text' => '148 106 7'],
            'dark' => ['accent' => '233 185 73', 'contrast' => '26 21 8', 'deep' => '56 40 6', 'soft' => '48 40 20', 'text' => '240 200 110'],
        ],
        'gray' => [
            'light' => ['accent' => '90 87 78', 'contrast' => '255 255 255', 'deep' => '42 40 34', 'soft' => '238 236 229', 'text' => '77 74 66'],
            'dark' => ['accent' => '168 162 150', 'contrast' => '20 19 16', 'deep' => '30 29 25', 'soft' => '40 38 33', 'text' => '190 184 172'],
        ],
        'black' => [
            'light' => ['accent' => '42 40 34', 'contrast' => '255 255 255', 'deep' => '12 11 9', 'soft' => '234 232 226', 'text' => '42 40 34'],
            'dark' => ['accent' => '201 196 184', 'contrast' => '19 18 15', 'deep' => '25 24 21', 'soft' => '42 40 36', 'text' => '208 203 191'],
        ],
    ];
    // Pages without a YAML color (typically areas) get the brand green so
    // they are not the only colorless pages on the site; unknown color
    // names still fall back to neutral black.
    $accent = $accentMap[$color ?? 'green'] ?? $accentMap['black'];
@endphp
<style>
    :root {
        --accent: {{ $accent['light']['accent'] }};
        --accent-contrast: {{ $accent['light']['contrast'] }};
        --accent-deep: {{ $accent['light']['deep'] }};
        --accent-soft: {{ $accent['light']['soft'] }};
        --accent-text: {{ $accent['light']['text'] }};
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --accent: {{ $accent['dark']['accent'] }};
            --accent-contrast: {{ $accent['dark']['contrast'] }};
            --accent-deep: {{ $accent['dark']['deep'] }};
            --accent-soft: {{ $accent['dark']['soft'] }};
            --accent-text: {{ $accent['dark']['text'] }};
        }
    }
</style>
