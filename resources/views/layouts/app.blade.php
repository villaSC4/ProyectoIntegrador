<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MediSign-ID')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('css/header-footer/footer/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header-footer/header/header.css') }}">
    @stack('styles') </head>
<body class="bg-gray-900">

    <div class="layout">
        <div id="header">
            @include('components.header')
        </div>

        <div class="contenido-derecho">
            <div id="footer">
                @include('components.footer')
            </div>

            <main class="contenido-principal">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands/hands.js" crossorigin="anonymous"></script>

    @stack('scripts')
</body>
</html>