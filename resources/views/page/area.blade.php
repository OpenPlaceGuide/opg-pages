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

        @php
            $description = Fallback::resolve($area->descriptions);
        @endphp
        @if($description)
            <p class="mt-5 max-w-prose text-lg">{{ $description }}</p>
        @endif

        <p class="mt-5">
            <x-share :title="$area->getFullName()" />
        </p>
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

            {{-- Group the ~100 place types into browsable themes. Keyed by the
                 type page slug from poitypes.csv; anything unmapped lands in
                 "More around town". Purely presentational — all cards are in
                 the server-rendered HTML, the filter only hides. --}}
            @php
                $typeGroupDefs = [
                    'Eat & drink' => ['restaurants', 'cafes', 'fast-food', 'food-courts', 'bars', 'pubs', 'biergartens', 'nightclubs', 'Ice-creams'],
                    'Shopping' => ['supermarkets', 'marketplaces', 'kiosks', 'butchers', 'greengrocer', 'beverages-shops', 'organic-shops', 'book-shops', 'buy-clothes', 'buy-flowers', 'shoe-shops', 'department-stores', 'diy-shops', 'electronics', 'electrical', 'computer-shops', 'furniture-shops', 'hardware', 'stationaries', 'rent-a-video'],
                    'Money & work' => ['banks', 'bureau-de-changes', 'insurances', 'accountants', 'lawyers', 'taxes', 'estate-agents', 'estate_agents', 'employment-agencies', 'advertising-agencies', 'travel-agents', 'offices', 'companies', 'businesses', 'administrative-offices', 'it', 'telecommunications', 'newspapers', 'architects', 'warehouses'],
                    'Health & beauty' => ['hospitals', 'clinics', 'doctors', 'dentists', 'pharmacies', 'pharamcies', 'opticians', 'verterinaries', 'beauty/spas', 'hairdressers', 'massages'],
                    'Learning & culture' => ['schools', 'kindergartens', 'colleges', 'universities', 'educational-institutions', 'music-schools', 'driving-schools', 'libraries', 'museums', 'art-centers', 'cinemas', 'theatres', 'researches', 'studios'],
                    'Sport & leisure' => ['stadiums', 'football-fields', 'tennis-courts', 'golf-courses', 'swimming-pools', 'bowling', 'gyms', 'gardens', 'zoos'],
                    'Getting around' => ['bus-stations', 'taxi', 'airports', 'parking', 'get-fuel', 'car-repair', 'car-sharing', 'car-shops', 'rent-a-car', 'bicycle-repair-stations'],
                    'Places to stay' => ['hotels', 'guest-houses', 'hostels', 'motels'],
                    'Civic & community' => ['governmental-offices', 'embassies', 'courthouses', 'police-stations', 'fire-stations', 'post-offices', 'post-boxes', 'prisons', 'public-buildings', 'public-toilets', 'public-telephones', 'community-center', 'associations', 'foundations', 'ngos', 'churches-mosques', 'religions', 'grave-yards', 'internet-cafes', 'laundries', 'dry-cleaners'],
                    'Streets, areas & buildings' => ['areas', 'cities', 'cities2', 'subcities', 'main-streets', 'secondary-streets', 'tertiary-streets', 'residential-roads', 'buildings', 'commercial-buildings', 'retail-buildings', 'houses', 'apartments', 'industrial', 'industrials'],
                ];
                $slugToGroup = [];
                foreach ($typeGroupDefs as $groupLabel => $groupSlugs) {
                    foreach ($groupSlugs as $groupSlug) {
                        $slugToGroup[$groupSlug] = $groupLabel;
                    }
                }
                $groupedTypes = array_fill_keys(array_keys($typeGroupDefs), []);
                $groupedTypes['More around town'] = [];
                foreach ($types as $groupedType) {
                    $groupedTypes[$slugToGroup[$groupedType->slug] ?? 'More around town'][] = $groupedType;
                }
                $groupedTypes = array_filter($groupedTypes);
            @endphp

            <label class="hidden relative block w-full max-w-xl mt-4" x-init="$el.classList.remove('hidden')">
                <span class="sr-only">Filter place categories</span>
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-ink/50 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="search" x-model="q" placeholder="Filter {{ count($types) }} categories…"
                    class="w-full rounded-full border-2 border-edge bg-surface pl-10 pr-4 py-2.5 text-base focus:border-ink focus:outline-none">
            </label>

            @foreach($groupedTypes as $groupLabel => $groupTypes)
                @php
                    $groupNames = Str::lower(implode('|', array_map(fn($t) => Fallback::resolve($t->plural), $groupTypes)));
                @endphp
                <section class="mt-8" data-names="{{ $groupNames }}"
                         x-show="q === '' || $el.dataset.names.includes(q.toLowerCase())">
                    <h3 class="m-0 flex items-center gap-2 font-display font-bold text-sm uppercase tracking-wider text-ink/70">
                        <span class="inline-block h-2 w-2 rounded-sm bg-accent" aria-hidden="true"></span>
                        {{ $groupLabel }}
                        <span class="font-sans font-normal normal-case tracking-normal text-ink/50 tabular-nums">{{ count($groupTypes) }}</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                        @foreach($groupTypes as $type)
                            <a class="card px-4 py-3 flex items-center gap-3"
                               data-name="{{ Str::lower(Fallback::resolve($type->plural)) }}"
                               x-show="q === '' || $el.dataset.name.includes(q.toLowerCase())"
                               href="{{ route('typesInArea.' . App::currentLocale(), ['areaSlug' => $area->slug, 'typeSlug' => $type->slug]) }}">
                                @php
                                    $logo = $type->getLogoUrl();
                                @endphp
                                @if($logo)
                                    <span class="relative flex h-10 w-10 shrink-0">
                                        <img
                                            class="aspect-square h-full w-full"
                                            alt=""
                                            src="{{ $logo }}"
                                        />
                                  </span>
                                @endif
                                <h4 class="text-base font-bold m-0 flex-grow">{{ ucfirst(Fallback::resolve($type->plural)) }}</h4>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </section>
@endsection
