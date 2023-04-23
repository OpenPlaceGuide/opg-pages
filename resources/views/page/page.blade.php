@extends('layouts.index')

<h1>
    @if($logoUrl)
        <img height="100" src="{{ asset($logoUrl) }} ">
    @endif

    {{ Fallback::field($main->tags, 'name') }}
</h1>


<script>
    function start() {
        var name = window.prompt('Name')
        window.open('');
    }
</script>
<a href="{{ $newPlaceUrl }}">Create short URL / start contributing</a>


<h2>Gallery</h2>

@foreach($gallery as $text => $mediaPath)
    <img height="100" src="{{ asset($mediaPath) }} ">
    <p>{{ $text }}</p>
@endforeach

<h2>Location(s)</h2>
@foreach($branches as $branch)
    <h3>{{ Fallback::field($branch->tags, 'name') }}</h3>

    <img src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'text' => Fallback::field($branch->tags, 'name')]) }}">
    <ul>
        <li><a href="{{ $branch->idInfo->getOsmUrl() }}" target="_blank">OSM Info</a></li>
        <li><a href="{{ $branch->idInfo->getOsmUrl('https://osmapp.org') }}" target="_blank">OSM App</a></li>
    </ul>
@endforeach
