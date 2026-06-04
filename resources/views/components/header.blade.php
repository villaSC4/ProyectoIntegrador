<header class="header-sidebar">
    <div class="header-logo">
        <img src="{{ asset('imagenes/Logo/Logo-grande.webp') }}" alt="MediSign" />

        <div class="header-logo-text">
            <h2>MediSign</h2>
            <p>Sistema de gestion</p>
        </div>
    </div>

    <nav class="header-menu">
        <a href="{{ url('/home') }}" class="header-link {{ Request::is('home') ? 'header-link-active' : '' }}" data-nav="inicio">
            <span class="header-icon"><img src="{{ asset('imagenes/header-footer/home.webp') }}" alt="" /></span>
            Inicio
        </a>

        <a href="{{ route('cita.reserva') }}" class="header-link {{ Request::is('reservar-cita*') ? 'header-link-active' : '' }}" data-nav="citas">
            <span class="header-icon"><img src="{{ asset('imagenes/header-footer/agenta.svg') }}" alt="" /></span>
            Agendar Cita
        </a>
    </nav>

    <div class="header-ayuda">
        <strong>Atencion inclusiva</strong>
        <span>Acceso para pacientes con soporte de lenguaje de señas.</span>
    </div>

    <div class="header-logout">
        @if(Auth::check())
            <form action="{{ route('logout') }}" method="POST" id="logout-form" style="display: none;">
                @csrf
            </form>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="logout-btn">
                <span class="header-logout-icon">
                    <img src="{{ asset('imagenes/header-footer/cerrar sesion.svg') }}" alt="Cerrar" />
                </span>
                Cerrar sesión
            </a>
        @else
            <a href="{{ route('login') }}" class="logout-btn">
                <span class="header-logout-icon">
                    <img src="{{ asset('imagenes/header-footer/cerrar sesion.svg') }}" alt="Cerrar" />
                </span>
                Cerrar sesión
            </a>
        @endif
    </div>
</header>