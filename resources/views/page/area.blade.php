@extends('layouts.index')

@section('pageTitle')
    Places in {{ $area->getFullName() }}
@endsection

@section('content')
    <header class="px-5 mt-10 max-w-5xl mx-auto">
        @if($parentArea)
            <nav class="mb-4">
                <a href="{{ $parentArea->getUrl() }}" class="text-sm text-ink/70">
                    ← Back to {{ Fallback::field($parentArea->tags, 'name') ?? ucfirst(str_replace('-', ' ', $parentArea->slug)) }}
                </a>
            </nav>
        @endif

        <h1 class="plate px-5 py-3 text-3xl md:text-4xl">{{ $area->getFullName() }}</h1>

        @php($description = Fallback::resolve($area->descriptions))
        @if($description)
            <p class="mt-5 max-w-prose text-lg">{{ $description }}</p>
        @endif
    </header>

    <!-- Mapillary Images Section -->
    <x-mapillary-gallery
        :images="$area->getMapillaryImages()"
        :title="'Community Street View Images from ' . $area->getFullName()"
        :location-name="$area->getFullName()"
    />

    <x-subarea-links
        :subareas="$subareas"
        :title="'Areas in ' . $area->getFullName()"
        :link-generator="fn($subarea) => $subarea->getUrl()"
    />

    <section>
        <div class="px-5 py-2 max-w-5xl mx-auto" x-data="{ q: '' }">
            <h2 class="section-title">Places in {{ $area->getFullName() }}</h2>

            <input type="search" x-model="q" placeholder="Filter…"
                class="hidden w-full max-w-sm rounded-md border-2 border-edge bg-surface px-3 py-2 text-sm focus:border-ink focus:outline-none"
                x-init="$el.classList.remove('hidden')">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-6">
                @foreach($types as $type)
                    <a class="card px-4 py-3 flex items-center justify-between gap-3"
                       data-name="{{ Str::lower(Fallback::resolve($type->plural)) }}"
                       x-show="q === '' || $el.dataset.name.includes(q.toLowerCase())"
                       href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $area->slug, 'typeSlug' => $type->slug]) }}">
                        <h3 class="text-base m-0">{{ ucfirst(Fallback::resolve($type->plural)) }}</h3>
                        @php($logo = $type->getLogoUrl())
                        @if($logo)
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
@endsection
