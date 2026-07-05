@extends('layouts.index')

@section('pageTitle')
    {{ Fallback::field($main->tags, 'name') }} - {{ Fallback::resolve($type->name) }} in {{ $branches[0]?->area?->getFullName() ?? '...' }}
@endsection

@section('content')
    <style>
        /* Animate the fade out effect */
        @keyframes fade-out {
            from {
                background-color: yellowgreen;
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
        }
    </style>
    <header>
        <h1 class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            @if($logoUrl)
                <img class="h-20 mr-5 mb-4 inline aspect-square" src="{{ asset($logoUrl) }} ">
            @else
                {{--                @svg("icon-${icon}_11","h-20 w-20 mr-5 mb-4 inline aspect-square fill-current text-$color-900" )--}}
            @endif

            <div class="hyphens-auto">
                {{ Fallback::field($main->tags, 'name') }}
            </div>
        </h1>
    </header>
    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <p class="float-right">
                @if($newPlaceUrl)
                    <x-github-button :href="$newPlaceUrl">Create URL / Add content</x-github-button>
                @endif
            </p>
            Welcome to the <a href="{{ url('/') }}">{{ config('app.name') }}</a> page of
            <strong>{{ Fallback::field($main->tags, 'name') }}</strong>, a
            <strong>{{ Fallback::resolve($type->name) }}</strong></strong>.

            <h2>Media</h2>

            <div class="overflow-x-auto flex space-x-4 flex-row w-full">
                <!-- Slides -->
                @foreach($gallery as $text => $mediaPath)
                    <div class="flex-none">
                        <figure class="inline-grid grid-cols-1 auto-rows-auto">
                            <img class="shadow-lg p-1 bg-white md:h-80 h-48 w-auto" src="{{ asset($mediaPath) }}">
                            <figcaption class="py-3 w-0 min-w-full">{{ $text }}</figcaption>
                        </figure>
                    </div>
                @endforeach
            </div>

            <x-github-button :href="$newPlaceUrl ?? $githubUrl">Add media</x-github-button>

            <h2>Location(s)</h2>
            @foreach($branches as $branch)
                <section id="{{ $branch->idInfo->getKey() }}">
                    <h3>{{ Fallback::field($branch->tags, 'name') }}</h3>
                    <strong>{{ ucfirst(Fallback::resolve($type->name)) }}</strong>
                    @if($branch->area !== null)
                        in <strong><a
                                href="<?php echo $branch->area->getUrl() ?>">{{ $branch->area->getFullName() }}</a></strong>
                    @endif

                    <ul class="space-y-2 list-disc pl-2">
                        @foreach((new \App\Services\TagRenderer($branch->tags))->getTagTexts() as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>

                    @php
                        $mainUrl = $branch->idInfo->getOsmUrl(url('/'))
                    @endphp
                    <a href="{{ $mainUrl }}" target="_blank">
                        <img class="shadow-lg" width="699" height="300"
                             alt="Map showing the address of {{  Fallback::field($branch->tags, 'name') }} in three different zoom levels."
                             src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'slug' => \App\Services\Language::slug(Fallback::field($branch->tags, 'name', language: 'en')), 'text' => Fallback::field($branch->tags, 'name')]) }}">
                    </a>
                    <ul class="flex">
                        <li><a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank">OSM Info</a></li>
                        <li><a href="{{ $mainUrl }}" target="_blank">Main page {{ config('app.name') }}</a>
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
                                <div class="flex-none rounded-lg bg-gray-200 h-48 md:h-80 aspect-video"></div>
                                <div class="flex-none rounded-lg bg-gray-200 h-48 md:h-80 aspect-video"></div>
                                <div class="flex-none rounded-lg bg-gray-200 h-48 md:h-80 aspect-video"></div>
                            </div>
                            <div class="h-4 w-48 bg-gray-200 rounded mb-4"></div>
                            <div class="h-8 w-40 bg-gray-200 rounded"></div>
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
                                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 no-underline">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
