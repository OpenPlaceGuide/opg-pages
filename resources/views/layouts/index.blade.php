<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
</head>
<body class="text-gray-700 bg-gradient-to-br from-white to-{{ $color }}-100 border-t-2 border-{{ $color }}-900 min-h-screen">
    @yield('content')

    <p>(C) OdBL OpenStreetMap Contributors, OpenPlaceGuide data repository contributors</p>
</body>
</html>



