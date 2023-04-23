@extends('layouts.index')

<h1>{{ ucfirst($type->getPlural()) }} in {{ $area->names['en'] }}</h1>

<h2>Location(s)</h2>

<p>There are {{ count($places) }} {{ $type->getPlural() }} found.</p>

@foreach($places as $place)
    <li>{{ $place->tags->name ?? 'no name' }}</li>
@endforeach
