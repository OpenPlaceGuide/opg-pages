<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
</head>
<body
    class="relative text-gray-700 bg-gradient-to-br from-white to-{{ $color }}-100 border-t-2 border-{{ $color }}-900 min-h-screen">
<div class="pb-10">
    @yield('content')
</div>
<p class="h-12 text-xs absolute bottom-0 right-0 px-5 py-5">(C) OdBL <a href="https://openstreetmap.org/">OpenStreetMap</a> contributors & <a href="https://openplaceguide.org">OpenPlaceGuide</a> data
    repository contributors</p>
</body>
</html>



