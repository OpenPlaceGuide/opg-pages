@extends('layouts.index')

<h1>{{ Fallback::resolve($area->names) }}</h1>
<h2>Types</h2>

@foreach($types as $type)
    <li><a href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $area->slug, 'typeSlug' => $type->slug]) }}">{{ ucfirst(Fallback::resolve($type->plural)) }}</a></li>
@endforeach
