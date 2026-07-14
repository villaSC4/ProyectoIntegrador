<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - MediSign</title>
    <link rel="stylesheet" href="{{ asset('css/paginas/Login/login.css') }}"/>
    <link rel="icon" href="{{ asset('imagenes/Logo/Logo-grande.webp') }}" type="image/webp" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />
</head>

<body class="centrado">

    <div class="container-izquierda mitad centrado">
        <img src="{{ asset('imagenes/imgLogin/Doctora.webp') }}" alt="Doctora" class="imagen-doctora" />
    </div>

    <div class="container-derecha mitad centrado">
        <img src="{{ asset('imagenes/Logo/Logo.webp') }}" alt="Logo" class="logo" />
        <p>Sistema de citas Médicas</p>
        <span>Acceder de forma rápida y segura</span>

        <div class="container-facil centrado">
            <p>Reconocimiento Facial</p>

            <div class="panel-Facil centrado" id="contenedor-video-login" style="position: relative;">
                <img class="panelescaneo" id="placeholder-login" src="{{ asset('imagenes/imgLogin/panelEscaneo.webp') }}" alt="Panel Escaneo" />
            </div>

            <div class="botones-h">
                <button id="btnEscanear" class="escaner boton">Escanear Rostro</button>
                
                <button onclick="window.location.href='{{ route('registro') }}'" class="CrearCuenta boton">Crear Cuenta</button>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="{{ asset('js/paginas/Login/login.js') }}"></script>
    <script>
        const reproducirIndicacion = () => {
            if ("speechSynthesis" in window) {
                window.speechSynthesis.cancel();
                const voz = new SpeechSynthesisUtterance("Bienvenido, escanee su rostro");
                voz.lang = "es-PE";
                voz.rate = 0.95;
                window.speechSynthesis.speak(voz);
            }
        };

        const iniciarFlujoVoz = () => {
            reproducirIndicacion();
            setInterval(reproducirIndicacion, 20000);
        };

        document.addEventListener("click", iniciarFlujoVoz, { once: true });
        document.addEventListener("keydown", iniciarFlujoVoz, { once: true });
    </script>

</body>
</html>