@extends('layouts.index')

@section('pageTitle')
    Not on the map yet - {{ config('app.name') }}
@endsection

@section('content')
    <header class="px-5 mt-10 max-w-3xl mx-auto">
        <h1 class="plate px-5 py-3 text-2xl md:text-3xl m-0">This place isn't on the map yet</h1>
    </header>
    <section>
        <div class="px-5 py-2 max-w-3xl mx-auto">
            <p class="mt-4 text-ink/80">
                We have this place in our directory, but its
                <a href="https://www.openstreetmap.org" target="_blank" rel="noopener">OpenStreetMap</a>
                data isn't available from our data source yet. Places that were just
                added or edited in OpenStreetMap can take a little while to appear here.
            </p>

            {{-- The remedy for the common case (someone just mapped this place): flush
                 this page's cached OSM lookup and try again. Same POST as the footer
                 "Refresh data" button; no @csrf because the endpoint is CSRF-exempt and
                 this page must stay publicly cacheable. --}}
            <form method="POST" action="{{ route('refreshCache') }}" class="mt-6">
                <input type="hidden" name="return" value="{{ request()->getRequestUri() }}">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                        <path d="M21 3v6h-6"></path>
                    </svg>
                    Refresh data
                </button>
            </form>

            @if(!empty($osmIds))
                <p class="mt-6 text-sm text-ink/70">
                    Check the object{{ count($osmIds) > 1 ? 's' : '' }} on OpenStreetMap:
                    @foreach($osmIds as $osmId)
                        <a href="{{ $osmId->getOsmUrl() }}" target="_blank" rel="noopener">{{ ucfirst($osmId->osmType) }} {{ $osmId->osmId }}</a>@if(!$loop->last), @endif
                    @endforeach
                </p>
            @endif

            <p class="mt-6">
                <a href="{{ config('app.url') }}">Go to the homepage</a>
            </p>
        </div>
    </section>
@stop
