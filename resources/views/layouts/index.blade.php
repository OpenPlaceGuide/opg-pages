<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>@yield('pageTitle', config('app.name'))</title>
    @hasSection('metaDescription')
        <meta name="description" content="@yield('metaDescription')">
    @endif
    @include('partials.accent')
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    @if(config('app.umami_website_id'))
        <script defer src="https://cloud.umami.is/script.js" data-website-id="{{ config('app.umami_website_id') }}"></script>
    @endif
    @if(isset($schemaMarkup))
        {!! (new \App\Services\SchemaOrg(\App\Services\Repository::getInstance()))->renderJsonLd($schemaMarkup) !!}
    @endif
</head>
<body class="bg-paper text-ink font-sans antialiased min-h-screen flex flex-col">
<div class="h-1.5 bg-accent" aria-hidden="true"></div>
<header class="border-b-2 border-edge">
    <div class="max-w-5xl mx-auto px-5 py-3 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="plate px-3 py-1 text-lg leading-tight">{{ config('app.name') }}</a>
        <p class="text-sm text-ink/70 m-0 hidden sm:block">Places &amp; businesses on the map</p>
    </div>
</header>
<main class="flex-1 pb-16">
    @yield('content')
</main>
<footer class="mt-auto border-t-2 border-edge bg-soft">
    <div class="max-w-5xl mx-auto px-5 py-6 text-sm flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
        <p class="m-0">&copy; OdBL <a href="https://openstreetmap.org/">OpenStreetMap</a> contributors &amp;
            <a href="https://openplaceguide.org">OpenPlaceGuide</a> data repository contributors</p>
        <div class="flex flex-wrap items-center gap-x-8 gap-y-4">
            @php($footerArea = $area ?? ($main ?? null)?->area)
            @if($footerArea?->idInfo)
                <p class="m-0"><a href="{{ route('newsfeed.' . App::currentLocale(), ['areaSlug' => $footerArea->slug]) }}">What's new in {{ Fallback::resolve($footerArea->names) ?: Fallback::field($footerArea->tags, 'name') }}</a></p>
            @endif
            <p class="m-0"><a href="https://github.com/OpenPlaceGuide/opg-pages">Source code</a> (AGPL)</p>
            <form method="POST" action="{{ route('refreshCache') }}" class="m-0">
                @csrf
                <input type="hidden" name="return" value="{{ request()->getRequestUri() }}">
                <button type="submit" class="btn-quiet" title="Reload the latest data for this page">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                        <path d="M21 3v6h-6"></path>
                    </svg>
                    Refresh data
                </button>
            </form>
        </div>
    </div>
</footer>
</body>
</html>
