@section('pageTitle')
    Places in {{ $area->getFullName() }}
@endsection

@extends('layouts.index')

<header>
    <h1 class="text-3xl px-5 mt-10 md:flex text-center items-center max-w-5xl mx-auto">
        <div class="hyphens-auto">
            {{ $area->getFullName() }}
        </div>
    </h1>

    @php($description = Fallback::resolve($area->descriptions))
    @if($description)
        <p class="px-5 mt-5 max-w-5xl mx-auto">{{ $description }}</p>
    @endif
</header>

<section>
    <div class="px-5 py-2 max-w-5xl mx-auto">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 grid-flow-row auto-rows-fr mt-6 w-full">
            @foreach($types as $type)
                <a class="no-underline px-4 flex flex-row justify-between items-center border text-card-foreground max-w-md bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl m-4"
                   href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $area->slug, 'typeSlug' => $type->slug]) }}">
                    <div class="flex-grow">
                        <h3 class="tracking-tight text-lg font-bold">{{ ucfirst(Fallback::resolve($type->plural)) }}</h3>
                    </div>
                    @php($logo = $type->getLogoUrl())
                    @if($logo)
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
