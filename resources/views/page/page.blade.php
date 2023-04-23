@extends('layouts.index')

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

<h1>
    @if($logoUrl)
        <img height="100" src="{{ asset($logoUrl) }} ">
    @endif

    {{ Fallback::field($main->tags, 'name') }}
</h1>

@if($newPlaceUrl)
    <a href="{{ $newPlaceUrl }}">Create short URL / start contributing</a>
@endif

<h2>Gallery</h2>

@foreach($gallery as $text => $mediaPath)
    <img height="100" src="{{ asset($mediaPath) }} ">
    <p>{{ $text }}</p>
@endforeach

<h2>Location(s)</h2>
@foreach($branches as $branch)
    <section id="{{ $branch->idInfo->getKey() }}">
        <h3>{{ Fallback::field($branch->tags, 'name') }}</h3>

        <img src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'slug' => \App\Services\Language::slug(Fallback::field($branch->tags, 'name', language: 'en')), 'text' => Fallback::field($branch->tags, 'name')]) }}">
        <ul>
            <li><a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank">OSM Info</a></li>
            <li><a href="{{ $branch->idInfo->getOsmUrl('https://osmapp.org') }}" target="_blank">OSM App</a></li>
        </ul>
    </section>
@endforeach
