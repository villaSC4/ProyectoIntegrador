<footer class="footer-usuario">
    <div class="footer-avatar">
        <img class="imgperfil" src="{{ asset('imagenes/header-footer/perfil.webp') }}" alt="Imagen de perfil">
    </div>

    <div class="footer-info">
        @auth
            <h2>{{ session('usuario_nombre') }}</h2>
            <p>DNI: {{ Auth::user()->dni }}</p>
        @else
            <h2>Usuario Invitado</h2>
            <p>DNI: 00000000</p>
        @endauth
    </div>
</footer>