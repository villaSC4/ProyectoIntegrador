<header class="header-sidebar">
    <div class="header-logo">
        <img src="{{ asset('imagenes/Logo/Logo-grande.webp') }}" alt="Citas Medicas">
        <div class="header-logo-text">
            <h2>Citas Medicas</h2>
            <p>Sistema de Gestion</p>
        </div>
    </div>

    <nav class="header-menu">
        <a href="{{ url('/') }}" class="header-link {{ Request::is('/') ? 'header-link-active' : '' }}">
            <span class="header-icon"><img src="{{ asset('imagenes/header-footer/home.webp') }}" alt=""></span>
            Inicio
        </a>

        <a href="#" class="header-link">
            <span class="header-icon"><img src="{{ asset('imagenes/header-footer/agenta.svg') }}" alt=""></span>
            Agendar Cita
        </a>
    </nav>

    <div class="header-logout">
        @if(Auth::check())
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn" style="background:none; border:none; cursor:pointer; color:inherit; display: flex; align-items: center; gap: 10px;">
                    <span class="header-logout-icon">
                        <img src="{{ asset('imagenes/header-footer/cerrar sesion.svg') }}" alt="Cerrar">
                    </span>
                    Cerrar Sesión
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="logout-btn" style="text-decoration: none; color:inherit; display: flex; align-items: center; gap: 10px;">
                <span class="header-logout-icon">
                    <img src="{{ asset('imagenes/header-footer/cerrar sesion.svg') }}" alt="Cerrar">
                </span>
                Cerrar Sesión
            </a>
        @endif
    </div>
</header>