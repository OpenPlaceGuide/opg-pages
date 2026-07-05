@extends('layouts.index')

@section('pageTitle')
    {{ Fallback::field($main->tags, 'name') }} - {{ Fallback::resolve($type->name) }} in {{ $branches[0]?->area?->getFullName() ?? '...' }}
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
                    <span class="chip font-semibold">{{ ucfirst(Fallback::resolve($type->name)) }}</span>
                    @if($branches[0]?->area)
                        <a href="{{ $branches[0]->area->getUrl() }}" class="chip">{{ $branches[0]->area->getFullName() }}</a>
                    @endif
                </p>
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

            <h2 class="section-title">Location(s)</h2>

            @if(count($branches) > 1)
                <nav class="card p-5 my-6">
                    <p class="m-0 mb-3 font-display font-bold text-sm uppercase tracking-wider text-ink/70">{{ count($branches) }} locations</p>
                    <ul class="columns-1 sm:columns-2 lg:columns-3 gap-6 m-0 list-none">
                        @foreach($branches as $branch)
                            <li class="m-0 ml-0 mb-2 break-inside-avoid">
                                <a href="#{{ $branch->idInfo->getKey() }}" class="text-sm">{{ Fallback::field($branch->tags, 'name') }}</a>
                                @if($branch->area)<span class="block text-xs text-ink/60">{{ $branch->area->getFullName() }}</span>@endif
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @foreach($branches as $branch)
                <section id="{{ $branch->idInfo->getKey() }}" class="card p-5 my-6 scroll-mt-4">
                    <h3 class="inline-block bg-accent-soft border-2 border-edge rounded px-3 py-1 m-0">{{ Fallback::field($branch->tags, 'name') }}</h3>
                    <p class="text-sm text-ink/70 mt-2">
                        <strong>{{ ucfirst(Fallback::resolve($type->name)) }}</strong>
                        @if($branch->area !== null)
                            in <strong><a
                                    href="<?php echo $branch->area->getUrl() ?>">{{ $branch->area->getFullName() }}</a></strong>
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
                    <a href="{{ $mainUrl }}" target="_blank" class="card inline-block overflow-hidden mt-4">
                        <img class="max-w-full h-auto m-0" width="699" height="300"
                             loading="lazy" decoding="async"
                             alt="Map showing the address of {{  Fallback::field($branch->tags, 'name') }} in three different zoom levels."
                             src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'slug' => \App\Services\Language::slug(Fallback::field($branch->tags, 'name', language: 'en')), 'text' => Fallback::field($branch->tags, 'name')]) }}">
                    </a>
                    <ul class="flex gap-4 mt-2 text-sm list-none">
                        <li class="m-0 ml-0"><a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank">OSM Info</a></li>
                        <li class="m-0 ml-0"><a href="{{ $mainUrl }}" target="_blank">Main page {{ config('app.name') }}</a>
                        </li>
                    </ul>

                    {{-- Mapillary street view images for this branch. The heading and a
                         skeleton are rendered immediately so users see what is loading and the
                         layout does not shift; the gallery itself is lazy-loaded on scroll and
                         replaces the skeleton. Empty branches are collapsed by the lazy loader.
                         Skeleton tiles use the same h-48/md:h-80 aspect-video size as the real
                         images so the swap does not move anything. --}}
                    <div class="mt-6 min-h-[20rem] md:min-h-[28rem]" data-lazy-src="{{ route('branch.mapillary', ['lat' => $branch->lat, 'lon' => $branch->lon]) }}">
                        <h2 class="text-xl font-bold mb-4">Community Street View Images</h2>
                        <div class="animate-pulse" aria-hidden="true">
                            <div class="flex space-x-4 w-full overflow-hidden mb-6">
                                <div class="flex-none rounded-lg bg-soft h-48 md:h-80 aspect-video"></div>
                                <div class="flex-none rounded-lg bg-soft h-48 md:h-80 aspect-video"></div>
                                <div class="flex-none rounded-lg bg-soft h-48 md:h-80 aspect-video"></div>
                            </div>
                            <div class="h-4 w-48 bg-soft rounded mb-4"></div>
                            <div class="h-8 w-40 bg-soft rounded"></div>
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
