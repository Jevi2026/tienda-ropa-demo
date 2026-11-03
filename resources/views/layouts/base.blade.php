<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('tienda.nombre')) · {{ config('tienda.nombre') }}</title>
    <meta name="description" content="@yield('description', 'Tienda de ropa en línea.')">
    @hasSection('noindex')
        <meta name="robots" content="noindex">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="@yield('body-class')" data-currency="{{ config('tienda.moneda') }}" data-decimals="{{ config('tienda.decimales') }}">
    @yield('body')
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
