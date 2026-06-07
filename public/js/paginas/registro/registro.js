const dniInput = document.getElementById("dni");
const nombreInput = document.getElementById("nombre");
const fechaInput = document.getElementById("fecha");
const celularInput = document.getElementById("celular");

const estadoDni = document.getElementById("estado-dni");
const estadoDatos = document.getElementById("estado-datos");
const estadoCelular = document.getElementById("estado-celular");
const estadoRostro = document.getElementById('estado-rostro');

const contenedor = document.getElementById('contenedor-video');
const placeholder = document.getElementById('placeholder-face');

let modelsLoaded = false;
let camaraStream = null;
let detectorInterval = null;
let descriptorRegistrado = null; 

function marcarEstado(elemento, estado) {
    if (!elemento) return;
    elemento.classList.remove("correcto", "error");
    if (estado === "correcto") elemento.classList.add("correcto");
    if (estado === "error") elemento.classList.add("error");
}

dniInput.addEventListener("input", function () {
    const valorDni = dniInput.value.trim();

    nombreInput.value = "";
    fechaInput.value = "";
    marcarEstado(estadoDni, "");
    marcarEstado(estadoDatos, "");

    if (valorDni.length === 8 && !isNaN(valorDni)) {
        marcarEstado(estadoDni, "correcto");

        fetch(`/api/consultar-dni/${valorDni}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    nombreInput.value = data.nombre;
                    fechaInput.value = data.fecha;
                    marcarEstado(estadoDatos, "correcto");
                    
                    verificarRequisitosParaIA(); 
                } else {
                    marcarEstado(estadoDatos, "error");
                }
            })
            .catch(error => {
                console.error("Error al consultar DNI:", error);
                marcarEstado(estadoDatos, "error");
            });
    } else if (valorDni.length > 0) {
        marcarEstado(estadoDni, "error");
    }
});

celularInput.addEventListener("input", function () {
    const valorCelular = celularInput.value.trim();
    const celularValido = /^(\+51\s?)?9\d{8}$/.test(valorCelular);

    if (valorCelular.length === 0) {
        marcarEstado(estadoCelular, "");
        return;
    }
    
    if (celularValido) {
        marcarEstado(estadoCelular, "correcto");
        verificarRequisitosParaIA(); 
    } else {
        marcarEstado(estadoCelular, "error");
    }
});

async function loadModels() {
    try {
        const MODEL_URL = 'https://raw.githubusercontent.com/vladmandic/face-api/master/model/';
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        modelsLoaded = true;
        console.log("=== MediSign IA: Modelos de Registro listos ===");
        
        verificarRequisitosParaIA();
    } catch (error) {
        console.error("Error al descargar modelos Face-API:", error);
    }
}
loadModels();

function verificarRequisitosParaIA() {
    const dniOk = estadoDni?.classList.contains('correcto');
    const datosOk = estadoDatos?.classList.contains('correcto');
    const celularOk = estadoCelular?.classList.contains('correcto');

    if (dniOk && datosOk && celularOk && modelsLoaded) {
        iniciarEscaneoFacial();
    }
}

async function iniciarEscaneoFacial() {
    if (document.getElementById('video-registro')) return;

    try {
        const video = document.createElement('video');
        video.id = 'video-registro';
        video.autoplay = true;
        video.muted = true;
        video.playsInline = true; 
        video.style.width = "100%";
        video.style.borderRadius = "10px";
        
        camaraStream = await navigator.mediaDevices.getUserMedia({ 
            video: { width: 640, height: 480 } 
        });
        video.srcObject = camaraStream;

        placeholder.style.display = 'none';
        contenedor.innerHTML = ""; 
        contenedor.appendChild(video);

        video.addEventListener('play', () => {
            let capturando = false;

            detectorInterval = setInterval(async () => {
                if (capturando) return;  

                const opcionesTiny = new faceapi.TinyFaceDetectorOptions({
                    inputSize: 416,
                    scoreThreshold: 0.5
                });

                const detection = await faceapi.detectSingleFace(video, opcionesTiny)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    capturando = true; 
                    
                    clearInterval(detectorInterval);
                    detectorInterval = null;
                    video.pause();

                    marcarEstado(estadoRostro, "correcto");
                    descriptorRegistrado = Array.from(detection.descriptor);
                    console.log("¡Rostro validado con éxito!");
                    
                    apagarHardwareCamara();
                }
            }, 800);
        });
    } catch (err) {
        console.error("No se pudo acceder a la cámara:", err);
    }
}

document.getElementById("btnTerminar").addEventListener("click", function() {
    const dni = dniInput.value.trim();
    const cel = celularInput.value.trim();
    const nombre = nombreInput.value.trim();
    const rostroOk = estadoRostro?.classList.contains('correcto');

    if (!dni || !cel || !nombre || !rostroOk || !descriptorRegistrado) {
        alert("Por favor, completa todos los campos del formulario e incluye el escaneo facial antes de terminar.");
        return;
    }

    const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        alert("Error de seguridad: Token CSRF no localizado.");
        return;
    }

    const datos = {
        dni: dni,
        nombre: nombre,
        celular: cel,
        face_vector: descriptorRegistrado, 
        _token: csrfToken 
    };

    fetch('/api/finalizar-registro', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("¡Registro exitoso! Tu perfil biométrico ha sido guardado en MySQL. Ya puedes iniciar sesión.");
            window.location.href = "/"; 
        } else {
            alert("Error al registrar en MediSign: " + data.message);
            location.reload(); 
        }
    })
    .catch(err => {
        console.error("Error crítico en el registro:", err);
        alert("Error de comunicación: No se pudo conectar con el controlador de Laravel.");
    });
});

function apagarHardwareCamara() {
    if (camaraStream) {
        camaraStream.getTracks().forEach(track => track.stop());
        camaraStream = null;
    }
}