<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MediSign - Pantalla del Paciente</title>
    <link rel="stylesheet" href="{{ asset('css/paginas/doctor/traductor/pantalla-paciente.css') }}" />
    <link rel="icon" href="{{ asset('imagenes/Logo/Logo-grande.webp') }}" type="image/webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  </head>
  <body>
    <main class="pantalla">
      <section class="avatar-panel">
        <div class="avatar-senas" data-avatar>
          <div class="avatar-cabeza"></div>
          <div class="avatar-cuerpo"></div>
          <div class="avatar-brazo brazo-izquierdo"></div>
          <div class="avatar-brazo brazo-derecho"></div>
        </div>
      </section>

      <section class="mensaje-panel">
        <div class="cabecera-paciente">
          <img src="{{ asset('imagenes/Logo/Logo-grande.webp') }}" alt="MediSign" />
          <div>
            <span>Mensaje del doctor</span>
            <strong>Avatar de lenguaje de se&ntilde;as</strong>
          </div>
        </div>

        <h1 data-texto-paciente>Esperando indicacion del doctor.</h1>
        <p data-fecha-paciente>La pantalla se actualizara en tiempo real cuando el doctor escriba, dicte o use un atajo.</p>

        <div class="acciones-paciente">
          <button type="button" data-detener-animacion>Detener animacion</button>
          <button type="button" data-limpiar-mensaje>Limpiar mensaje</button>
        </div>
      </section>
    </main>
    <script src="{{ asset('js/paginas/doctor/traductor/pantalla-paciente.js') }}"></script>
  </body>
</html>