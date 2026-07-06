@props([
    'title',
    'url' => null,
    'logo' => null,
    'lat' => null,
    'lon' => null,
])

@php
    $shareUrl = $url ?: url()->current();
    $hasCoords = $lat !== null && $lon !== null;

    if ($hasCoords) {
        $latF = (float) $lat;
        $lonF = (float) $lon;

        // Full-precision value for copying / linking; a trimmed value for display.
        $latPlain = rtrim(rtrim(number_format($latF, 6, '.', ''), '0'), '.');
        $lonPlain = rtrim(rtrim(number_format($lonF, 6, '.', ''), '0'), '.');
        $coordsCopy = $latPlain . ', ' . $lonPlain;

        $plusCode = \App\Services\OpenLocationCode::encode($latF, $lonF);

        $geoName = rawurlencode($title);
        $geoUri = sprintf('geo:%s,%s?q=%s,%s(%s)', $latPlain, $lonPlain, $latPlain, $lonPlain, $geoName);
        $osmUrl = sprintf('https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=18/%s/%s', $latPlain, $lonPlain, $latPlain, $lonPlain);
    }
@endphp

<div x-data="shareDialog(@js(['url' => $shareUrl, 'title' => $title]))" class="inline-block">
    <button type="button" @click="show()"
            class="btn-quiet" aria-haspopup="dialog">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
        </svg>
        Share
    </button>

    {{-- Modal overlay --}}
    <div x-cloak x-show="open" @click="hide()"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 px-4 py-8"
         role="dialog" aria-modal="true" aria-label="Share {{ $title }}"
         x-transition.opacity>
        <div @click.stop
             class="card w-full max-w-md p-6 my-auto"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4">
                <h2 class="m-0 font-display font-bold text-xl hyphens-auto">Share: {{ $title }}</h2>
                <button type="button" @click="hide()" class="shrink-0 text-ink/60 hover:text-ink -mr-1 -mt-1 p-1" aria-label="Close">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Link --}}
            <p class="mt-5 mb-2 text-xs font-bold uppercase tracking-wider text-ink/60">Link</p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ $shareUrl }}"
                       @focus="$event.target.select()"
                       class="min-w-0 flex-1 rounded border-2 border-edge bg-surface px-3 py-2 text-sm">
                <button type="button" @click="copy('{{ $shareUrl }}', 'link')" class="btn-quiet shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span x-text="copied === 'link' ? 'Copied' : 'Copy'">Copy</span>
                </button>
            </div>

            {{-- Native (system) share — mobile mainly --}}
            <button type="button" x-cloak x-show="canShare" @click="nativeShare()" class="btn-primary mt-3 w-full justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                Share via…
            </button>

            {{-- QR code --}}
            <div class="mt-5 flex justify-center">
                <div class="relative inline-block rounded bg-white p-3">
                    <canvas x-ref="qr" class="block h-40 w-40" aria-label="QR code linking to this page"></canvas>
                    @if($logo)
                        <img src="{{ $logo }}" alt=""
                             class="absolute left-1/2 top-1/2 h-1/4 w-1/4 -translate-x-1/2 -translate-y-1/2 rounded bg-white object-contain p-0.5">
                    @endif
                </div>
            </div>

            @if($hasCoords)
                {{-- Coordinates --}}
                <p class="mt-6 mb-2 text-xs font-bold uppercase tracking-wider text-ink/60">Coordinates</p>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="min-w-0 flex-1 rounded border-2 border-edge bg-surface px-3 py-2 text-sm tabular-nums">
                            {{ $latPlain }}°&nbsp;&nbsp;{{ $lonPlain }}°
                        </div>
                        <button type="button" @click="copy('{{ $coordsCopy }}', 'coords')" class="btn-quiet shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <span x-text="copied === 'coords' ? 'Copied' : 'Copy'">Copy</span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="min-w-0 flex-1 rounded border-2 border-edge bg-surface px-3 py-2 text-sm">
                            <span class="text-ink/50">Plus code</span> <span class="tabular-nums font-semibold">{{ $plusCode }}</span>
                        </div>
                        <button type="button" @click="copy('{{ $plusCode }}', 'plus')" class="btn-quiet shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <span x-text="copied === 'plus' ? 'Copied' : 'Copy'">Copy</span>
                        </button>
                    </div>
                </div>

                {{-- Open in --}}
                <p class="mt-6 mb-2 text-xs font-bold uppercase tracking-wider text-ink/60">Open in</p>
                <div class="grid grid-cols-1 gap-2">
                    <a href="{{ $geoUri }}" class="card no-underline flex items-center gap-3 px-4 py-2.5 text-sm hover:border-ink">
                        <svg class="w-4 h-4 shrink-0 text-accent-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        Maps app on this device
                    </a>
                    <a href="{{ $osmUrl }}" target="_blank" rel="noopener" class="card no-underline flex items-center gap-3 px-4 py-2.5 text-sm hover:border-ink">
                        <svg class="w-4 h-4 shrink-0 text-accent-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        OpenStreetMap.org
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
