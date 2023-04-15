<img height="100" src="{{ asset($logoUrl) }} ">

{{ $main->tags->name }}

<h2>Gallery</h2>

@foreach($gallery as $text => $mediaPath)
    <img height="100" src="{{ asset($mediaPath) }} ">
    <p>{{ $text }}</p>
@endforeach

<h2>Location(s)</h2>
@foreach($branches as $branch)
    <h3>{{ $branch->tags->name ?? 'no default name' }}</h3>

    <img src="{{ route('tripleZoomMap', ['lat' => $branch->lat, 'lon' => $branch->lon, 'text' => $branch->tags->name ?? 'no name']) }}">

@endforeach
