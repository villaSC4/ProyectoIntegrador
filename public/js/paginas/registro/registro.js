const dniInput = document.getElementById("dni");
const nombreInput = document.getElementById("nombre");
const fechaInput = document.getElementById("fecha");
const celularInput = document.getElementById("celular");

const estadoDni = document.getElementById("estado-dni");
const estadoDatos = document.getElementById("estado-datos");
const estadoCelular = document.getElementById("estado-celular");

const contenedor = document.getElementById('contenedor-video');
const placeholder = document.getElementById('placeholder-face');
let modelsLoaded = false;

function marcarEstado(elemento, estado) {
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
                console.error("Error al consultar:", error);
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
    const MODEL_URL = 'https://raw.githubusercontent.com/vladmandic/face-api/master/model/';
    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    modelsLoaded = true;
    console.log("Modelos cargados");
}
loadModels();

function verificarRequisitosParaIA() {
    const dniOk = document.getElementById('estado-dni').classList.contains('correcto');
    const datosOk = document.getElementById('estado-datos').classList.contains('correcto');
    const celularOk = document.getElementById('estado-celular').classList.contains('correcto');

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
        
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;

        placeholder.style.display = 'none';
        contenedor.innerHTML = ""; 
        contenedor.appendChild(video);

        video.addEventListener('play', () => {
            const interval = setInterval(async () => {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    marcarEstado(document.getElementById('estado-rostro'), "correcto");
                    window.faceDescriptor = Array.from(detection.descriptor);
                    console.log("¡Rostro validado!");
                    
                    stream.getTracks().forEach(track => track.stop());
                    clearInterval(interval);
                }
            }, 1000);
        });
    } catch (err) {
        console.error("No se pudo acceder a la cámara:", err);
    }
}

document.getElementById("btnTerminar").addEventListener("click", function() {
    const dni = dniInput.value.trim();
    const cel = celularInput.value.trim();
    const nombre = nombreInput.value.trim();
    const rostroOk = document.getElementById('estado-rostro').classList.contains('correcto');

    if (!dni || !cel || !nombre || !rostroOk || !window.faceDescriptor) {
        alert("Por favor, completa todos los pasos, incluyendo el escaneo facial.");
        return;
    }

    const datos = {
        dni: dni,
        nombre: nombre,
        celular: cel,
        face_vector: window.faceDescriptor,
        _token: document.querySelector('input[name="_token"]').value 
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
            alert("¡Registro exitoso! Ya puedes iniciar sesión.");
            window.location.href = "/"; 
        } else {
            alert("Error al registrar: " + data.message);
        }
    })
    .catch(err => console.error("Error en el registro:", err));
});