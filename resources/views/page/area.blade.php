@extends('layouts.index')

<h1>{{ $area->names['en'] }}</h1>
<h2>Types</h2>

@foreach($types as $type)
    <li><a href="{{ route('typesInArea', ['areaSlug' => $area->slug, 'typeSlug' => $type->slug]) }}">{{ ucfirst($type->plural['en'] ?? 'no name') }}</a></li>
@endforeach
