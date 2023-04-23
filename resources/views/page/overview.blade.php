@extends('layouts.index')

<h1>{{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) }}</h1>

<h2>Location(s)</h2>

<p>There are {{ count($places) }} {{ Fallback::resolve($type->plural) }} found.</p>

@foreach($places as $place)
    <li><a href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">{{ Fallback::field($place->tags, 'name') }}</a></li>
@endforeach
