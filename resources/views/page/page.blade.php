@extends('layouts.index')

<img height="100" src="{{ asset($logoUrl) }} ">

{{ $main->tags->name }}


<script>
    function start() {
        var name = window.prompt('Name')
        window.open('https://github.com/OpenPlaceGuide/data/new/main/places/'+ name + '/place.yaml?value={!! urlencode($newPlaceContent) !!}');
    }
</script>
<a href="javascript:start()">Create short URL / start contributing</a>


<h2>Gallery</h2>

@foreach($gallery as $text => $mediaPath)
    <img height="100" src="{{ asset($mediaPath) }} ">
    <p>{{ $text }}</p>
@endforeach

<h2>Location(s)</h2>
@foreach($branches as $branch)
    <h3>{{ Language::field($branch->tags, 'name') }}</h3>

    <img src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'text' => Language::field($branch->tags, 'name') }}">
    <a href="{{ $branch->idInfo->getOsmUrl() }}">OSM Info</a>

@endforeach
