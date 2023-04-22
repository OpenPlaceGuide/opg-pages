@extends('layouts.index')

{{ ucfirst($type->getPlural()) }} in XYZ

<h2>Location(s)</h2>

<p>There are {{ count($places) }} {{ $type->getPlural() }} found.</p>

@foreach($places as $place)
    <li>{{ $place->tags->name ?? 'no name' }}</li>
@endforeach
