@extends('layouts.index')

@section('content')
    <h1>{{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) }}</h1>

    <h2>{{ count($places) }} {{ Fallback::resolve($type->plural) }} found:</h2>

    @foreach($places as $place)
        <li><a href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">{{ Fallback::field($place->tags, 'name') }}</a></li>
    @endforeach
@stop
