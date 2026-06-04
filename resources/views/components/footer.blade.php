<footer class="footer-usuario">
    <div class="footer-identidad">
        <div class="footer-avatar">
            <img class="imgperfil" src="{{ asset('imagenes/header-footer/perfil.webp') }}" alt="Imagen de perfil" />
        </div>

        <div class="footer-info">
            @auth
                <h2>{{ Auth::user()->nombre ?? 'Paciente MediSign' }}</h2>
                <p>DNI: {{ Auth::user()->dni }}</p>
            @else
                <h2>Usuario Invitado</h2>
                <p>DNI: --------</p>
            @endauth
        </div>
    </div>

    <div class="footer-estado">
        @auth
            <span>Sesión activa</span>
            <strong>Paciente autenticado</strong>
        @else
            <span>Sin sesión</span>
            <strong>Invitado</strong>
        @endauth
    </div>
</footer>