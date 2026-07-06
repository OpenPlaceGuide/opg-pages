@extends('layouts.index')

@section('pageTitle')
    What's new in {{ Fallback::resolve($area->names) ?: $area->getFullName() }}
@endsection

@section('content')
    <header class="px-5 mt-10 max-w-5xl mx-auto">
        <h1 class="plate px-5 py-3 text-2xl md:text-3xl">
            What's new in <a href="{{ route('page.' . App::currentLocale(), ['slug' => $area->slug]) }}" class="underline decoration-2 underline-offset-4 text-accent-contrast">{{ Fallback::resolve($area->names) ?: $area->getFullName() }}</a>
        </h1>
        <p class="mt-4 max-w-prose text-ink/70">
            Places on the map that were added or edited by
            <a href="https://www.openstreetmap.org">OpenStreetMap</a> contributors in the last {{ $days }} days (up to {{ $limit }} places).
        </p>
    </header>

    <section>
        <div class="px-5 py-2 mt-4 max-w-5xl mx-auto">
            @if(count($places) === 0)
                <p class="mt-3 max-w-prose">No changes in the last {{ $days }} days.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-6">
                    @foreach($places as $place)
                        <a class="card px-4 py-3 flex items-center justify-between gap-3"
                           href="{{ \App\Services\Repository::getInstance()->getUrl($place) }}">
                            <div class="flex-grow">
                                <h3 class="text-base m-0">{{ Fallback::field($place->tags, 'name') }}</h3>
                                <p class="text-xs text-ink/70 m-0 mt-1">
                                    {{ \Carbon\Carbon::parse($place->meta->timestamp)->diffForHumans() }}@if($place->meta->user) by {{ $place->meta->user }}@endif
                                </p>
                            </div>
                            <span class="chip text-xs shrink-0">{{ $place->meta->isNew() ? 'New' : 'Updated' }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@stop
