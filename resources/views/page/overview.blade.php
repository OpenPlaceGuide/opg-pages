@extends('layouts.index')

@section('pageTitle')
    {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) }}
@endsection

@section('content')
    <header>
        <h1 class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            @if($logoUrl)
                <img class="h-20 mr-5 mb-4 inline aspect-square" src="{{ asset($logoUrl) }} ">
            @endif
            <div class="hyphens-auto">
                {{ ucfirst(Fallback::resolve($type->plural)) }} in <a href="{{ route('page.' . App::currentLocale(), ['slug' => $area->slug]) }}">{{ Fallback::resolve($area->names) }}</a>
            </div>
        </h1>
        @php($typeDescription = Fallback::resolve($type->descriptions))
        @if($typeDescription)
            <p class="px-5 mt-5 max-w-5xl mx-auto">{{ $typeDescription }}</p>
        @endif

        @php($description = Fallback::resolve($area->descriptions))
        @if($description)
            <p class="px-5 mt-5 max-w-5xl mx-auto">{{ $description }}</p>
        @endif
    </header>

    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <h2>{{ count($places) }} {{ Fallback::resolve($type->plural) }} found:</h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 grid-flow-row auto-rows-fr mt-6 w-full">
                @foreach($places as $place)
                    <a class="no-underline px-4 flex flex-row justify-between items-center border text-card-foreground max-w-md bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl m-4"
                       href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">
                        @if (\App\Services\Repository::getInstance()->isFeatured($place->idInfo))
                            <div class="flex-shrink-0">
                                <span
                                    class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full mr-4">
                                    <img
                                        class="aspect-square h-full w-full"
                                        alt="Business Logo"
                                        src="{{  \App\Services\Repository::getInstance()->resolvePlace($place->idInfo)?->getLogoUrl() }}"
                                    />
                              </span>
                            </div>
                        @endif
                        <div class="flex-grow">
                            <h3 class="tracking-tight text-lg font-bold">{{ Fallback::field($place->tags, 'name') }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@stop
