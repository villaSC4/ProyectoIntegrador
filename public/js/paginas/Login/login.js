// =========================================================================
// 👤 ELEMENTOS DEL DOM (EXCLUSIVOS DE LA PANTALLA DE LOGIN)
// =========================================================================
const btnEscanear = document.getElementById('btnEscanear');
const contenedor = document.getElementById('contenedor-video-login');
const placeholder = document.getElementById('placeholder-login');

// Variables de control locales protegidas
let modelosListos = false;
let camaraStream = null;
let detectorInterval = null;

// Bloquear el botón estéticamente hasta que los modelos terminen de descargar
if (btnEscanear) btnEscanear.disabled = true;

// =========================================================================
// 🧠 1. CARGA ASÍNCRONA DE MODELOS (AL CARGAR LA PÁGINA)
// =========================================================================
async function cargarModelos() {
    try {
        const MODEL_URL = 'https://raw.githubusercontent.com/vladmandic/face-api/master/model/';
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        
        modelosListos = true;
        if (btnEscanear) {
            btnEscanear.disabled = false;
            console.log("=== MediSign: Modelos biométricos de Login listos ===");
        }
    } catch (error) {
        console.error("Error al descargar los modelos de Face-API:", error);
    }
}
cargarModelos();

// =========================================================================
// ⚡ 2. EVENTO CLIC: ENCENDER CÁMARA Y ESCANEAR
// =========================================================================
if (btnEscanear) {
    btnEscanear.addEventListener('click', async () => {
        if (!modelosListos) {
            alert("Los modelos aún se están descargando. Por favor, espere un momento.");
            return;
        }

        // Si ya hubiera un flujo corriendo por error, lo limpiamos antes de empezar
        limpiarFlujoCamara();

        try {
            const video = document.createElement('video');
            video.id = 'video-login';
            video.autoplay = true;
            video.muted = true;
            video.playsInline = true;
            video.style.width = "100%";
            video.style.borderRadius = "10px";

            if (placeholder) placeholder.style.display = 'none';
            if (contenedor) {
                contenedor.innerHTML = ""; // Limpieza segura de iconos previos
                contenedor.appendChild(video);
            }

            // Encendemos la webcam físicamente
            camaraStream = await navigator.mediaDevices.getUserMedia({ 
                video: { width: 640, height: 480 } 
            });
            video.srcObject = camaraStream;

            video.addEventListener('play', () => {
                console.log("=== Escaneo de Login Iniciado ===");
                let capturando = false;

                detectorInterval = setInterval(async () => {
                    if (capturando) return; 

                    const opcionesDetector = new faceapi.TinyFaceDetectorOptions({
                        inputSize: 416, 
                        scoreThreshold: 0.5 
                    });

                    const detection = await faceapi.detectSingleFace(video, opcionesDetector)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (detection && !capturando) {
                        // 🌟 1. Bloqueamos el paso inmediatamente de forma síncrona
                        capturando = true;
                        
                        // 🌟 2. Matamos el intervalo en el acto para que no existan ciclos fantasmas
                        clearInterval(detectorInterval);
                        detectorInterval = null;
                        
                        video.pause();

                        const descriptorActual = Array.from(detection.descriptor);
                        
                        // 🌟 3. Apagamos los lentes de la cámara de inmediato
                        apagarHardwareCamara();
                        
                        // 🌟 4. Recién enviamos los datos cuando el entorno JS está en completo silencio
                        verificarIdentidad(descriptorActual);
                    }
                }, 800);
            });

        } catch (err) {
            console.error("Error al acceder a la cámara:", err);
            alert("Permiso denegado o hardware de cámara no disponible.");
        }
    });
}

// =========================================================================
// 🚀 3. COMUNICACIÓN ASÍNCRONA CON TU CONTROLADOR LARAVEL
// =========================================================================
function verificarIdentidad(descriptor) {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (!metaToken) {
        alert("Error de configuración: Falta el token CSRF en el HTML.");
        return;
    }
    const token = metaToken.getAttribute('content');

    fetch('/api/rostro/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token 
        },
        body: JSON.stringify({ vector_actual: descriptor })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error("Error en el servidor: Código " + res.status);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            alert("¡Bienvenido, " + data.usuario + "!");
            window.location.href = "/home";
        } else {
            alert("Rostro no reconocido en el sistema.");
            if (placeholder) placeholder.style.display = 'block';
            const videoLogin = document.getElementById('video-login');
            if (videoLogin) videoLogin.remove();
        }
    })
    .catch(err => {
        console.error("Error crítico detectado:", err);
        alert("Error 419: La sesión de seguridad de Laravel expiró. Por favor, refresca la página manualmente.");
    });
}

function limpiarFlujoCamara() {
    if (detectorInterval) {
        clearInterval(detectorInterval);
        detectorInterval = null;
    }
    apagarHardwareCamara();
}

function apagarHardwareCamara() {
    if (camaraStream) {
        camaraStream.getTracks().forEach(track => track.stop());
        camaraStream = null;
    }
}