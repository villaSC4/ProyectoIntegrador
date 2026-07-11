const btnEscanear = document.getElementById('btnEscanear');
const contenedor = document.getElementById('contenedor-video-login');
const placeholder = document.getElementById('placeholder-login');

let modelosListos = false;
let camaraStream = null;
let flujoActual = 0;
let escaneoEnCurso = false;

if (btnEscanear) btnEscanear.disabled = true;

async function cargarModelos() {
    try {
        const MODEL_URL = 'https://raw.githubusercontent.com/vladmandic/face-api/master/model/';
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

        modelosListos = true;
        if (btnEscanear) {
            btnEscanear.disabled = false;
            btnEscanear.textContent = 'Escanear Rostro';
            console.log('MediSign: modelos biometricos de login listos');
        }
    } catch (error) {
        console.error('Error al descargar los modelos de Face-API:', error);
        if (btnEscanear) {
            btnEscanear.disabled = false;
            btnEscanear.textContent = 'Reintentar modelos';
        }
    }
}

cargarModelos();

if (btnEscanear) {
    btnEscanear.addEventListener('click', iniciarEscaneo);
}

async function iniciarEscaneo() {
    if (!modelosListos || escaneoEnCurso) {
        if (!modelosListos) {
            alert('Los modelos biometricos aun se estan descargando. Espera unos segundos.');
        }
        return;
    }

    const flujo = ++flujoActual;
    escaneoEnCurso = true;
    btnEscanear.disabled = true;
    btnEscanear.textContent = 'Preparando camara...';
    limpiarFlujoCamara();
    prepararVistaCamara();

    try {
        camaraStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            },
            audio: false
        });

        const video = document.getElementById('video-login');
        video.srcObject = camaraStream;
        await video.play();

        await cuentaRegresiva(flujo);
        if (!flujoEstaActivo(flujo)) return;

        actualizarEstadoEscaneo(
            'Analizando rostro',
            'Manten tu cara centrada, mira la camara y permanece sin anteojos',
            ''
        );
        btnEscanear.textContent = 'Analizando rostro...';

        const descriptores = await buscarRostro(video, flujo);
        if (!descriptores || !descriptores.length || !flujoEstaActivo(flujo)) {
            throw new Error('No se detecto un rostro claro dentro del tiempo permitido.');
        }

        actualizarEstadoEscaneo(
            'Rostro detectado',
            'Validando tu identidad, espera un momento',
            ''
        );
        apagarHardwareCamara();
        await verificarIdentidad(descriptores, flujo);
    } catch (error) {
        if (!flujoEstaActivo(flujo)) return;

        console.error('Error durante el escaneo facial:', error);
        apagarHardwareCamara();
        restaurarPanel();
        alert(error.message || 'No se pudo completar el escaneo facial. Intentalo de nuevo.');
    }
}

function prepararVistaCamara() {
    if (!contenedor) return;

    contenedor.innerHTML = '';
    contenedor.classList.add('escaneo-activo');

    const video = document.createElement('video');
    video.id = 'video-login';
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;

    const estado = document.createElement('div');
    estado.id = 'estado-escaneo-login';
    estado.className = 'estado-escaneo-login';
    estado.innerHTML = `
        <strong id="titulo-escaneo-login">Preparate para el escaneo</strong>
        <span id="mensaje-escaneo-login">Mira la camara, mantente quieto, sin anteojos y coloca tu rostro dentro del recuadro</span>
        <b id="cuenta-escaneo-login">5</b>
    `;

    contenedor.appendChild(video);
    contenedor.appendChild(estado);
}

async function cuentaRegresiva(flujo) {
    for (let segundos = 5; segundos >= 1; segundos--) {
        if (!flujoEstaActivo(flujo)) return;

        actualizarEstadoEscaneo(
            'Preparate para el escaneo',
            segundos === 1 ? 'Mira la camara, sin anteojos y no te muevas' : 'Coloca tu rostro dentro del recuadro y quitate los anteojos',
            segundos
        );
        await esperar(1000);
    }
}

async function buscarRostro(video, flujo) {
    const opcionesDetector = new faceapi.TinyFaceDetectorOptions({
        inputSize: 416,
        scoreThreshold: 0.45
    });
    const tiempoMaximo = Date.now() + 12000;
    const descriptores = [];

    while (Date.now() < tiempoMaximo && flujoEstaActivo(flujo)) {
        const deteccion = await faceapi.detectSingleFace(video, opcionesDetector)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (deteccion) {
            descriptores.push(Array.from(deteccion.descriptor));
            if (descriptores.length >= 3) return descriptores;
            await esperar(300);
            continue;
        }

        await esperar(350);
    }

    return descriptores.length ? descriptores : null;
}

function actualizarEstadoEscaneo(titulo, mensaje, cuenta) {
    const tituloElemento = document.getElementById('titulo-escaneo-login');
    const mensajeElemento = document.getElementById('mensaje-escaneo-login');
    const cuentaElemento = document.getElementById('cuenta-escaneo-login');

    if (tituloElemento) tituloElemento.textContent = titulo;
    if (mensajeElemento) mensajeElemento.textContent = mensaje;
    if (cuentaElemento) {
        cuentaElemento.textContent = cuenta;
        cuentaElemento.classList.toggle('oculto', cuenta === '');
    }
}

async function verificarIdentidad(descriptores, flujo) {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (!metaToken) {
        throw new Error('Error de configuracion: falta el token CSRF en el HTML.');
    }

    const respuesta = await fetch('/api/rostro/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': metaToken.getAttribute('content')
        },
        body: JSON.stringify({
            vector_actual: descriptores[0],
            vector_actuales: descriptores
        })
    });

    if (!respuesta.ok) {
        throw new Error('El servidor no pudo procesar el escaneo. Codigo: ' + respuesta.status);
    }

    const datos = await respuesta.json();
    if (datos.success) {
        console.log('Autenticacion facial exitosa');
        window.location.href = datos.redirect_to;
        return;
    }

    if (!flujoEstaActivo(flujo)) return;
    restaurarPanel();
    alert(datos.message || 'Rostro no reconocido en el sistema.');
}

function restaurarPanel() {
    apagarHardwareCamara();
    escaneoEnCurso = false;

    if (contenedor) {
        contenedor.classList.remove('escaneo-activo');
        contenedor.innerHTML = '';
        if (placeholder) {
            placeholder.style.display = 'block';
            contenedor.appendChild(placeholder);
        }
    }

    if (btnEscanear) {
        btnEscanear.disabled = false;
        btnEscanear.textContent = 'Escanear Rostro';
    }
}

function limpiarFlujoCamara() {
    apagarHardwareCamara();
    if (contenedor) contenedor.classList.remove('escaneo-activo');
}

function apagarHardwareCamara() {
    if (camaraStream) {
        camaraStream.getTracks().forEach(track => track.stop());
        camaraStream = null;
    }
}

function flujoEstaActivo(flujo) {
    return flujo === flujoActual;
}

function esperar(milisegundos) {
    return new Promise(resolve => setTimeout(resolve, milisegundos));
}
