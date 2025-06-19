@extends('layouts.index')

@section('pageTitle')
    {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) }}
@endsection

@section('content')
    <header>
        <div class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            @if($logoUrl)
                <img class="h-20 mr-5 mb-4 inline aspect-square" src="{{ asset($logoUrl) }} ">
            @endif
            <h1 class="flex-grow hyphens-auto">
                {{ ucfirst(Fallback::resolve($type->plural)) }} in <a href="{{ route('page.' . App::currentLocale(), ['slug' => $area->slug]) }}">{{ Fallback::resolve($area->names) }}</a>
            </h1>
            <div class="flex justify-end">
                <x-map-button :href="$mapLink">To the Map</x-map-button>
            </div>
        </div>

        <h2 class="text-2xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
            What do you find here?
        </h2>
        @php($typeDescription = Fallback::resolve($type->descriptions))
        @if($typeDescription)
            <p class="px-5 mt-3 max-w-5xl mx-auto">{{ $typeDescription }}</p>
        @endif

        <section class="px-5 mt-3 max-w-5xl mx-auto">
            @foreach((new \App\Services\TagRenderer(\App\Services\TagRenderer::tagListToObject($type->tags)))->getTagTexts() as $line)
                <p>{{ $line }}.</p>
            @endforeach
        </section>

        @php($description = Fallback::resolve($area->descriptions))
        @if($description)
            <h2 class="text-2xl px-5 mt-4 md:flex text-center items-center max-w-5xl mx-auto">
                About this area
            </h2>

            <p class="px-5 mt-3 max-w-5xl mx-auto">{{ $description }}</p>
        @endif
    </header>

    <section>
        <h3 class="text-xl px-5 mt-4 md:flex text-center items-center max-w-5xl mx-auto">There are {{ count($places) }} {{ Fallback::resolve($type->plural) }} here:</h3>

        <div class="px-5 py-2 max-w-5xl mx-auto">

            <div class="grid md:grid-cols-2 lg:grid-cols-3 grid-flow-row auto-rows-fr mt-2 w-full">
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
                                        src="{{ \App\Services\Repository::getInstance()->resolvePlace($place->idInfo)?->getLogoUrl() }}"
                                    />
                              </span>
                            </div>
                        @endif
                        <div class="flex-grow">
                            <h3 class="tracking-tight text-lg font-bold">{{ Fallback::field($place->tags, 'name') }}</h3>
                        </div>
                        @php($logo = $type->getLogoUrl())
                        @if($logo && !\App\Services\Repository::getInstance()->isFeatured($place->idInfo))
                            <div class="flex-shrink-0">
                            <span
                                class="relative flex h-10 w-10 shrink-0 mr-4">
                                <img
                                    class="aspect-square h-full w-full"
                                    alt="Type Logo"
                                    src="{{ $logo }}"
                                />
                            </span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@stop
