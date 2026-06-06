<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MediSign - Panel Médico')</title>
    
    <link rel="icon" href="{{ asset('imagenes/Logo/Logo-grande.webp') }}" type="image/webp" />
    <preconnect href="https://fonts.googleapis.com" />
    <preconnect href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    @stack('doctor-styles')
</head>
<body>
    <div class="doctor-layout">
        
        <aside class="doctor-menu">
            <div class="marca-panel">
                <img src="{{ asset('imagenes/Logo/Logo-grande.webp') }}" alt="MediSign" />
                <div>
                    <h1>MediSign</h1>
                    <span>Panel médico</span>
                </div>
            </div>
            <nav class="nav-doctor">
                <a class="{{ request()->routeIs('doctor.panel') ? 'activo' : '' }}" href="{{ route('doctor.panel') }}">Panel</a>
                <a class="{{ request()->routeIs('doctor.traductor-senas') ? 'activo' : '' }}" href="{{ route('doctor.traductor-senas') }}">Traductor de Señas</a>
                
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="display: block;">
                    Cerrar sesión
                </a>
                
            </nav>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <div class="menu-ayuda">
                <strong>Consultorio 204</strong>
                <span>Calendario, pacientes de Dermatología y acceso rápido a historiales.</span>
            </div>
        </aside>

        @yield('doctor-content')

    </div>

    @stack('doctor-scripts')
</body>
</html>