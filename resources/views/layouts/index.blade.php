<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('pageTitle', config('app.name'))</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    @if(config('app.umami_website_id'))
        <script defer src="https://cloud.umami.is/script.js" data-website-id="{{ config('app.umami_website_id') }}"></script>
    @endif
</head>
<body
    class="relative text-gray-700 bg-gradient-to-br from-white to-{{ $color ?? 'black' }}-100 border-t-2 border-{{ $color ?? 'black' }}-900 min-h-screen">
<div class="pb-10">
    @yield('content')
</div>
<p class="h-12 text-xs absolute bottom-0 right-0 px-5 py-5">(C) OdBL <a href="https://openstreetmap.org/">OpenStreetMap</a> contributors & <a href="https://openplaceguide.org">OpenPlaceGuide</a> data
    repository contributors</p>
</body>
</html>



