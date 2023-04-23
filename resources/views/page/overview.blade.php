@extends('layouts.index')

<h1>{{ ucfirst(Language::resolve($type->plural)) }} in {{ Language::resolve($area->names) }}</h1>

<h2>Location(s)</h2>

<p>There are {{ count($places) }} {{ Language::resolve($type->plural) }} found.</p>

@foreach($places as $place)
    <li><a href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">{{ Language::field($place->tags, 'name') }}</a></li>
@endforeach
