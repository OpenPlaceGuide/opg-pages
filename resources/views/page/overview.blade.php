@extends('layouts.index')

@section('content')
    <header>
        <h1 class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            @svg("icon-${icon}_11","h-20 w-20 mr-5 mb-4 inline aspect-square fill-current text-$color-900" )
            <div class="hyphens-auto">
                {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) }}
            </div>
        </h1>
    </header>

    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <h2>{{ count($places) }} {{ Fallback::resolve($type->plural) }} found:</h2>

            <ul class="grid md:grid-cols-2 lg:grid-cols-3 grid-flow-row auto-rows-fr mt-6 w-full">
                @foreach($places as $place)
                    <a class="bg-white max-w-xs no-underline flex items-center mr-8 mb-8 p-4 rounded-lg shadow border-2 border-slate-500"
                       href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">
                        <span>
                            {{ Fallback::field($place->tags, 'name') }}
                        </span>
                    </a>
                @endforeach
            </ul>
        </div>
    </section>
@stop
