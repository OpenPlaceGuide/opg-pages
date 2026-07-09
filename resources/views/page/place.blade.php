@extends('layouts.index')

@php
    $shareName = Fallback::field($main->tags, 'name');
    // Only overlay a real business logo (featured places) in the QR centre;
    // type-logo fallbacks are generic and not worth it.
    $shareLogo = ($place && $place->logo) ? asset($logoUrl) : null;
@endphp

@section('pageTitle')
    {{ Fallback::field($main->tags, 'name') }} - {{ Fallback::resolve($type->name) }} in {{ $branches[0]?->area?->getFullName() ?? '...' }}
@endsection

{{-- Page-level share lives in the site header. It shares the page URL only:
     coordinates and "open in" links are per-location and live on each branch's
     own share button below, since a place can have many branches. --}}
@section('headerShare')
    <x-share :title="$shareName" :logo="$shareLogo" />
@endsection

@section('content')
    <style>
        /* Animate the fade out effect */
        @keyframes fade-out {
            from {
                background-color: rgb(var(--accent-soft));
            }
            to {
                background-color: transparent;
            }
        }

        /* Apply the fade out animation to the targeted section */
        section:target {
            animation-name: fade-out;
            animation-duration: 2s; /* Change this value to adjust the animation speed */
            animation-timing-function: ease-out;
            scroll-margin-top: 1rem;
        }
    </style>
    <header class="px-5 mt-10 max-w-5xl mx-auto">
        <div class="md:flex items-start gap-5">
            @if($logoUrl)<img class="h-20 mb-4 md:mb-0 aspect-square" src="{{ asset($logoUrl) }}" alt="">@endif
            <div>
                <h1 class="plate px-5 py-3 text-3xl md:text-4xl hyphens-auto m-0">
                    {{ Fallback::field($main->tags, 'name') }}
                    @php
                        $amharicName = $main->tags->{'name:am'} ?? null;
                    @endphp
                    @if($amharicName && $amharicName !== Fallback::field($main->tags, 'name'))
                        <span class="block text-xl md:text-2xl font-bold mt-1">{{ $amharicName }}</span>
                    @endif
                </h1>
                <p class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    @if($branches[0]?->area)
                        <a href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $branches[0]->area->slug, 'typeSlug' => $type->slug]) }}" class="chip font-semibold">{{ ucfirst(Fallback::resolve($type->name)) }}</a>
                        <a href="{{ $branches[0]->area->getUrl() }}" class="chip">{{ $branches[0]->area->getFullName() }}</a>
                    @else
                        <span class="chip font-semibold">{{ ucfirst(Fallback::resolve($type->name)) }}</span>
                    @endif
                </p>

                {{-- Quick actions, from OSM tags. Only for single-location places:
                     with many branches these would ambiguously point at one of them. --}}
                @if(count($branches) === 1)
                    @php
                        $qaTags = $branches[0]->tags;
                        // OSM multi-value phone tags use ';' but ',' occurs in the wild
                        // too. Offer every number: lines are often broken in Ethiopia,
                        // so callers need the alternatives.
                        $qaPhones = $qaTags->phone ?? $qaTags->{'contact:phone'} ?? '';
                        $qaPhones = array_values(array_filter(array_map('trim', preg_split('/[;,]/', $qaPhones))));
                        $qaWebsite = \App\Services\TagRenderer::safeWebsiteUrl($qaTags->website ?? $qaTags->{'contact:website'} ?? null);
                        // Social handles are usually stored as a bare username in Ethiopia,
                        // but a full profile URL occurs too; socialUrl() handles both.
                        $qaTiktok = \App\Services\TagRenderer::socialUrl('tiktok', $qaTags->{'contact:tiktok'} ?? $qaTags->tiktok ?? null);
                        $qaInstagram = \App\Services\TagRenderer::socialUrl('instagram', $qaTags->{'contact:instagram'} ?? $qaTags->instagram ?? null);
                        $qaTelegram = \App\Services\TagRenderer::socialUrl('telegram', $qaTags->{'contact:telegram'} ?? $qaTags->telegram ?? null);
                    @endphp
                    <p class="mt-4 flex flex-wrap items-center gap-2">
                        @foreach($qaPhones as $qaPhone)
                            <a href="tel:{{ preg_replace('/[^+0-9]/', '', $qaPhone) }}" class="btn-primary">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Call {{ $qaPhone }}
                            </a>
                        @endforeach
                        @if($qaWebsite)
                            <a href="{{ $qaWebsite }}" target="_blank" rel="noopener" class="btn-quiet">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                Website
                            </a>
                        @endif
                        @if($qaTiktok)
                            <a href="{{ $qaTiktok }}" target="_blank" rel="noopener" class="btn-quiet">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.53 1.5h3.02c.18 1.6.98 3.06 2.2 4.05a5.9 5.9 0 0 0 3.25 1.28v3.05a9 9 0 0 1-3.9-.9 9.4 9.4 0 0 1-1.53-.94l.02 6.63a6.63 6.63 0 1 1-5.7-6.57v3.24a3.4 3.4 0 1 0 2.42 3.26V1.5z"/></svg>
                                TikTok
                            </a>
                        @endif
                        @if($qaInstagram)
                            <a href="{{ $qaInstagram }}" target="_blank" rel="noopener" class="btn-quiet">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                                Instagram
                            </a>
                        @endif
                        @if($qaTelegram)
                            <a href="{{ $qaTelegram }}" target="_blank" rel="noopener" class="btn-quiet">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.94 4.4l-3.33 15.7c-.25 1.11-.91 1.38-1.84.86l-5.1-3.76-2.46 2.37c-.27.27-.5.5-1.02.5l.36-5.2 9.46-8.55c.41-.36-.09-.57-.64-.2L5.94 13.4l-5.03-1.57c-1.1-.34-1.12-1.09.23-1.62L20.5 2.9c.91-.34 1.71.2 1.44 1.5z"/></svg>
                                Telegram
                            </a>
                        @endif
                        {{-- Links to the place's main map page (e.g. /node/12345): OsmApp has a
                             directions button there but no deep link straight to directions. --}}
                        <a href="{{ $branches[0]->idInfo->getOsmUrl(url('/')) }}" target="_blank" rel="noopener" class="btn-quiet">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                            Directions
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </header>
    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <p class="m-0 text-sm md:text-base">
                    Welcome to the <a href="{{ url('/') }}">{{ config('app.name') }}</a> page of
                    <strong>{{ Fallback::field($main->tags, 'name') }}</strong>, a
                    <strong>{{ Fallback::resolve($type->name) }}</strong>.
                </p>
                @if($newPlaceUrl)
                    <p class="m-0">
                        <x-github-button :href="$newPlaceUrl">Create URL / Add content</x-github-button>
                    </p>
                @endif
            </div>

            @if(count($gallery) > 0)
                <h2 class="section-title">Media</h2>

                <div class="overflow-x-auto flex space-x-4 flex-row w-full snap-x pb-2">
                    <!-- Slides -->
                    @foreach($gallery as $text => $mediaPath)
                        <div class="flex-none snap-start">
                            <figure class="inline-grid grid-cols-1 auto-rows-auto">
                                <img class="card p-1 md:h-80 h-48 w-auto" src="{{ asset($mediaPath) }}" alt="{{ $text }}">
                                <figcaption class="py-3 w-0 min-w-full text-sm text-ink/70">{{ $text }}</figcaption>
                            </figure>
                        </div>
                    @endforeach
                </div>

                <x-github-button :href="$newPlaceUrl ?? $githubUrl">Add media</x-github-button>
            @else
                {{-- No media yet: one quiet invitation instead of an empty gallery strip. --}}
                <div class="mt-6 border-2 border-dashed border-edge rounded p-5 flex flex-wrap items-center justify-between gap-4">
                    <p class="m-0 text-sm text-ink/70">No photos of {{ Fallback::field($main->tags, 'name') }} yet — add the first one.</p>
                    <x-github-button :href="$newPlaceUrl ?? $githubUrl">Add media</x-github-button>
                </div>
            @endif

            <h2 class="section-title">Location(s)</h2>

            @if(count($branches) > 1)
                @php
                    // Branch names mostly repeat the place name ("Commercial Bank of
                    // Ethiopia (Furi Branch)" x70), so the index shows only what
                    // differs: when a branch name starts with the place name
                    // (case-insensitively, to survive OSM capitalisation drift),
                    // show just the remainder. Grouped by sub-area.
                    $navMainName = Fallback::field($main->tags, 'name') ?? '';
                    $navGroups = [];
                    foreach ($branches as $navBranch) {
                        $navName = Fallback::field($navBranch->tags, 'name') ?? '';
                        $navLabel = $navName;
                        if ($navMainName !== '' && mb_stripos($navName, $navMainName) === 0) {
                            $navLabel = mb_substr($navName, mb_strlen($navMainName));
                            // Drop the wrapping "(...)"/dashes the remainder was set off with.
                            $navLabel = preg_replace('/^[\s()\-–—]+|[\s()\-–—]+$/u', '', $navLabel);
                        }
                        $navGroups[$navBranch->area?->getFullName() ?? ''][] = [
                            'key' => $navBranch->idInfo->getKey(),
                            'label' => $navLabel !== '' ? $navLabel : $navName,
                        ];
                    }
                    ksort($navGroups);
                @endphp
                <nav class="card p-5 my-6">
                    <p class="m-0 font-display font-bold text-sm uppercase tracking-wider tabular-nums text-ink/70">{{ count($branches) }} locations</p>
                    @foreach($navGroups as $navArea => $navEntries)
                        @if($navArea !== '' && count($navGroups) > 1)
                            <p class="m-0 mt-4 mb-1 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-ink/60">
                                <span class="inline-block h-2 w-2 rounded-sm bg-accent" aria-hidden="true"></span>{{ $navArea }}
                            </p>
                        @elseif($navArea !== '')
                            <p class="m-0 mt-1 text-xs text-ink/60">{{ $navArea }}</p>
                        @endif
                        <ul class="columns-1 sm:columns-2 lg:columns-3 gap-6 m-0 mt-2 list-none">
                            @foreach($navEntries as $navEntry)
                                <li class="m-0 ml-0 mb-1.5 break-inside-avoid">
                                    <a href="#{{ $navEntry['key'] }}" class="text-sm">{{ $navEntry['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </nav>
            @endif

            @foreach($branches as $branch)
                <section id="{{ $branch->idInfo->getKey() }}" class="card p-5 my-6 scroll-mt-4">
                    @if(count($branches) > 1 || Fallback::field($branch->tags, 'name') !== Fallback::field($main->tags, 'name'))
                        <h3 class="inline-block m-0 pb-1 border-b-4 border-accent/70">{{ Fallback::field($branch->tags, 'name') }}</h3>
                    @endif
                    <p class="text-sm text-ink/70 mt-2">
                        @if($branch->area !== null)
                            <strong><a
                                    href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $branch->area->slug, 'typeSlug' => $type->slug]) }}">{{ ucfirst(Fallback::resolve($type->name)) }}</a></strong>
                            in <strong><a
                                    href="{{ $branch->area->getUrl() }}">{{ $branch->area->getFullName() }}</a></strong>
                        @else
                            <strong>{{ ucfirst(Fallback::resolve($type->name)) }}</strong>
                        @endif
                    </p>

                    <ul class="space-y-1.5 mt-3">
                        @foreach((new \App\Services\TagRenderer($branch->tags))->getTagTexts() as $line)
                            <li class="list-none ml-0 relative pl-5 before:content-[''] before:absolute before:left-0 before:top-[0.45em] before:h-2 before:w-2 before:rounded-sm before:bg-accent">{{ $line }}</li>
                        @endforeach
                    </ul>

                    @php
                        $mainUrl = $branch->idInfo->getOsmUrl(url('/'))
                    @endphp
                    <a href="{{ $mainUrl }}" target="_blank" class="card map-frame inline-block overflow-hidden mt-4">
                        <img class="max-w-full h-auto m-0" width="699" height="300"
                             loading="lazy" decoding="async"
                             alt="Map showing the address of {{  Fallback::field($branch->tags, 'name') }} in three different zoom levels."
                             src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'slug' => \App\Services\Language::slug(Fallback::field($branch->tags, 'name', language: 'en')), 'text' => Fallback::field($branch->tags, 'name')]) }}">
                    </a>
                    {{-- Actions for this location, below its map. The per-location
                         share links to this exact branch (page URL + #branch anchor)
                         and carries this branch's own coordinates, plus code and
                         "open in" links. --}}
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank" rel="noopener" class="btn-quiet">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            OSM Info
                        </a>
                        <a href="{{ $mainUrl }}" target="_blank" rel="noopener" class="btn-quiet">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                            Main page {{ config('app.name') }}
                        </a>
                        <x-share
                            :title="Fallback::field($branch->tags, 'name')"
                            :logo="$shareLogo"
                            :url="url()->current() . '#' . $branch->idInfo->getKey()"
                            :lat="$branch->lat"
                            :lon="$branch->lon"
                        />
                    </div>

                    {{-- Mapillary street view images for this branch. The heading and a
                         skeleton are rendered immediately so users see what is loading; the
                         gallery itself is lazy-loaded on scroll and replaces the skeleton.
                         Empty branches are collapsed by the lazy loader. The skeleton mirrors
                         the rendered gallery markup (heading, card-framed aspect-video tiles
                         with a caption line, contribute button — see x-mapillary-gallery) so
                         the content around it does not move on the swap. --}}
                    <div class="mt-6 min-h-[20rem] md:min-h-[28rem]" data-lazy-src="{{ route('branch.mapillary', ['lat' => $branch->lat, 'lon' => $branch->lon]) }}">
                        <h2 class="section-title mb-4">Community Street View Images</h2>
                        <div class="animate-pulse" aria-hidden="true">
                            <div class="flex space-x-4 w-full overflow-hidden mb-6 pb-2">
                                @foreach(range(1, 3) as $skeletonTile)
                                    <div class="flex-none">
                                        <div class="card p-1 h-48 md:h-80 aspect-video">
                                            <div class="h-full w-full rounded-sm bg-soft"></div>
                                        </div>
                                        <div class="py-3">
                                            <div class="h-5 w-40 bg-soft rounded"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2 h-10 w-44 bg-soft rounded-full"></div>
                        </div>
                    </div>

                    {{-- Mangrove reviews for this branch (lazy-loaded on scroll) --}}
                    <div class="mt-6" data-lazy-src="{{ route('branch.mangrove', ['lat' => $branch->lat, 'lon' => $branch->lon]) }}"></div>

                    {{-- Write review button for this branch --}}
                    @if(!empty($mangroveReviewUrls) && is_array($mangroveReviewUrls))
                        @foreach($mangroveReviewUrls as $key => $reviewOption)
                            @if(isset($reviewOption['branch_key']) && $reviewOption['branch_key'] === $branch->idInfo->getKey())
                                <div class="mt-4">
                                    <a href="{{ $reviewOption['url'] }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="btn-primary mt-4">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Write a Review
                                    </a>
                                </div>
                                @break
                            @endif
                        @endforeach
                    @endif
                </section>
            @endforeach
        </div>


    </section>
@stop
