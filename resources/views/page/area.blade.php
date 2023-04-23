@extends('layouts.index')

<h1>{{ Language::resolve($area->names) }}</h1>
<h2>Types</h2>

@foreach($types as $type)
    <li><a href="{{ route('typesInArea', ['areaSlug' => $area->slug, 'typeSlug' => $type->slug]) }}">{{ ucfirst(Language::resolve($type->plural) }}</a></li>
@endforeach
