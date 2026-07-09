@extends('layouts.index')

@section('pageTitle')
    {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::resolve($area->names) ?: $area->getFullName() }}
@endsection

@section('headerShare')
    <x-share :title="ucfirst(Fallback::resolve($type->plural)) . ' in ' . ($area->getFullName())" />
@endsection

@section('content')
    <header class="px-5 mt-10 max-w-5xl mx-auto">
        @if($parentArea)
            <nav class="mb-4">
                <a href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $parentArea->slug, 'typeSlug' => $type->slug]) }}" class="text-sm text-ink/70">
                    ← Back to {{ ucfirst(Fallback::resolve($type->plural)) }} in {{ Fallback::field($parentArea->tags, 'name') ?? ucfirst(str_replace('-', ' ', $parentArea->slug)) }}
                </a>
            </nav>
        @endif

        <div class="md:flex items-center gap-5">
            @if($logoUrl)
                <img class="h-20 mb-4 md:mb-0 aspect-square" src="{{ asset($logoUrl) }}" alt="">
            @endif
            <h1 class="plate px-5 py-3 text-2xl md:text-3xl">
                {{ ucfirst(Fallback::resolve($type->plural)) }} in <a href="{{ route('page.' . App::currentLocale(), ['slug' => $area->slug]) }}" class="underline decoration-2 underline-offset-4 text-accent-contrast">{{ Fallback::resolve($area->names) ?: $area->getFullName() }}</a>
            </h1>
        </div>

        <p class="mt-5">
            <x-share :title="ucfirst(Fallback::resolve($type->plural)) . ' in ' . ($area->getFullName())" />
        </p>
    </header>

    <div class="px-5 mt-8 max-w-5xl mx-auto">
        <h2 class="section-title">
            What do you find here?
        </h2>
        @php($typeDescription = Fallback::resolve($type->descriptions))
        @if($typeDescription)
            <p class="mt-3 max-w-prose">{{ $typeDescription }}</p>
        @endif

        <section class="mt-3 max-w-prose">
            @foreach((new \App\Services\TagRenderer(\App\Services\TagRenderer::tagListToObject($type->tags)))->getTagTexts() as $line)
                <p>{{ $line }}.</p>
            @endforeach
        </section>

        @php($description = Fallback::resolve($area->descriptions))
        @if($description)
            <h2 class="section-title mt-4">
                About this area
            </h2>

            <p class="mt-3 max-w-prose">{{ $description }}</p>
        @endif
    </div>

    <x-subarea-links
        :subareas="$subareas"
        :title="ucfirst(Fallback::resolve($type->plural)) . ' in Subareas'"
        :link-generator="fn($subarea) => route('typesInArea.' . App::currentLocale(), ['areaSlug' => $subarea->slug, 'typeSlug' => $type->slug])"
        :type="$type"
    />

    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto">
            <h2 class="section-title">There are {{ count($places) }} {{ Fallback::resolve($type->plural) }} here:</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-6">
                @foreach($places as $place)
                    <a class="card px-4 py-3 flex items-center justify-between gap-3"
                       href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">
                        @if (\App\Services\Repository::getInstance()->isFeatured($place->idInfo))
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden">
                                <img
                                    class="aspect-square h-full w-full"
                                    alt="Business Logo"
                                    src="{{ \App\Services\Repository::getInstance()->resolvePlace($place->idInfo)?->getLogoUrl() }}"
                                />
                          </span>
                        @endif
                        <div class="flex-grow">
                            <h3 class="text-base m-0">{{ Fallback::field($place->tags, 'name') }}</h3>
                            @if (\App\Services\Repository::getInstance()->isFeatured($place->idInfo))
                                <span class="chip text-xs">Featured</span>
                            @endif
                        </div>
                        @php($logo = $type->getLogoUrl())
                        @if($logo && !\App\Services\Repository::getInstance()->isFeatured($place->idInfo))
                            <span class="relative flex h-8 w-8 shrink-0">
                                <img
                                    class="aspect-square h-full w-full"
                                    alt="Type Logo"
                                    src="{{ $logo }}"
                                />
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@stop
