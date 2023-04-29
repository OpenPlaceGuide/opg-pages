@extends('layouts.index')

@section('content')
    <header>
        <h1 class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            <x-phosphor-bank class="h-20 w-20 mr-5 mb-4 inline aspect-square text-blue-900" />
            <div class="hyphens-auto">
                {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) }}
            </div>
        </h1>
    </header>

    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <h2>{{ count($places) }} {{ Fallback::resolve($type->plural) }} found:</h2>
            @foreach($places as $place)
                <li>
                    <a href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">{{ Fallback::field($place->tags, 'name') }}</a>
                </li>
            @endforeach
        </div>
    </section>
@stop
