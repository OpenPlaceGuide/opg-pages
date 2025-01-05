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
            Welcome to the <a href="{{ url('/') }}">{{ config('app.name') }}</a> page of <strong>{{ Fallback::field($main->tags, 'name') }}</strong>, a <strong>{{ Fallback::resolve($type->name) }}</strong></strong>.

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
                        in <strong><a href="<?php echo $branch->area->getUrl() ?>">{{ $branch->area->getFullName() }}</a></strong>
                    @endif

                    <ul>
                    @foreach(\App\Services\TagRenderer::getTagText($branch) as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                    </ul>

                    @php($mainUrl = $branch->idInfo->getOsmUrl(url('/')))
                    <a href="{{ $mainUrl }}" target="_blank">
                        <img class="shadow-lg" width="699" height="300" alt="Map showing the address of {{  Fallback::field($branch->tags, 'name') }} in three different zoom levels."
                            src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'slug' => \App\Services\Language::slug(Fallback::field($branch->tags, 'name', language: 'en')), 'text' => Fallback::field($branch->tags, 'name')]) }}">
                    </a>
                    <ul class="flex">
                        <li><a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank">OSM Info</a></li>
                        <li><a href="{{ $mainUrl }}" target="_blank">Main page {{ config('app.name') }}</a>
                        </li>
                    </ul>
                </section>
            @endforeach
        </div>


    </section>
@stop
