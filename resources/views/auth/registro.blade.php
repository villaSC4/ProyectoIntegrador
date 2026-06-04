<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Medign</title>
    <link rel="stylesheet" href="{{ asset('css/paginas/registro/registro.css') }}">
    <link rel="icon" href="{{ asset('imagenes/Logo/Logo-grande.webp') }}" type="image/webp">
</head>

<body class="cuerpo-completo">

    <section class="diseno-fondo">
        <img src="{{ asset('imagenes/imgLogin/Doctora.webp') }}" alt="Doctora" class="imagen-doctora">
    </section>

    <main class="container-back">
        <a href="{{ route('login') }}" class="volver-inicio">Volver al inicio</a>

        <section class="registration-area">
            <div class="header-text">
                <h1>Crear Cuenta</h1>
                <p>Ingresa tus datos para validar tu identidad</p>
            </div>

            <div class="registration-card">
                <div class="form-inputs">
                    <h2>Datos de Registro</h2>
                    
                    @csrf 

                    <div class="field">
                        <label>DNI</label>
                        <input id="dni" type="text" placeholder="7XXXXXXXX" maxlength="8">
                    </div>

                    <div class="field">
                        <label>Nombre Completo</label>
                        <input id="nombre" type="text" placeholder="Nombre Completo" readonly>
                    </div>

                    <div class="field">
                        <label>Fecha de Nacimiento</label>
                        <input id="fecha" type="text" placeholder="dd/mm/yyyy" readonly>
                    </div>

                    <div class="field">
                        <label>Número de Celular</label>
                        <input id="celular" type="text" placeholder="+51 9XXXXXXXX">
                    </div>

                    <button class="boton-registro" id="btnTerminar">Terminar</button>
                </div>

                <div class="face-panel">
                    <h3>Reconocimiento Facial</h3>
                    <p>Validación automática en tiempo real</p>

                    <div class="scanner-box" id="contenedor-video" style="position: relative;">
                        <img src="{{ asset('imagenes/imgRegistro/face.webp') }}" id="placeholder-face" alt="Reconocimiento facial">
                    </div>

                    <div class="status-stack">
                        <div class="pill" id="estado-dni"><span></span> DNI ingresado</div>
                        <div class="pill" id="estado-datos"><span></span> Datos verificados</div>
                        <div class="pill" id="estado-celular"><span></span> Celular ingresado</div>
                        <div class="pill" id="estado-rostro"><span></span> Rostro validado</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="{{ asset('js/paginas/registro/registro.js') }}"></script>
</body>

</html>