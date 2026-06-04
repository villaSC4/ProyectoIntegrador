const contenedor = document.getElementById('contenedor-ia');
const placeholder = document.getElementById('placeholder-mano');
const textoEstado = document.getElementById('texto-estado');
const resultadoSena = document.getElementById('resultado-sena');
const botonHorario = document.querySelector('.boton-horario');
const botonReintentar = document.getElementById('btn-reintentar');

window.especialidadSeleccionadaId = null;
let senaDetectada = false; 
let streamCamara = null; 

const hands = new Hands({
    locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`
});

hands.setOptions({
    maxNumHands: 1, 
    modelComplexity: 1,
    minDetectionConfidence: 0.6, 
    minTrackingConfidence: 0.6
});

hands.onResults(onResultsHands);

const videoElement = document.createElement('video');
videoElement.id = 'video-senas';
videoElement.style.width = '100%';
videoElement.style.height = '100%';
videoElement.style.objectFit = 'cover';
videoElement.autoplay = true;
videoElement.playsInline = true;

const camera = new Camera(videoElement, {
    onFrame: async () => {
        if (!senaDetectada) {
            await hands.send({ image: videoElement });
        }
    },
    width: 640,
    height: 480
});

function encenderCamara() {
    camera.start().then(() => {
        if (placeholder) placeholder.style.display = 'none';
        textoEstado.innerText = "¡Cámara activa! Muestre su mano firmemente frente a la pantalla.";
        textoEstado.style.color = ""; 
        textoEstado.style.fontWeight = "";
        contenedor.appendChild(videoElement);
        streamCamara = videoElement.srcObject;
        videoElement.style.opacity = "1"; 
    }).catch(err => {
        console.error("Error al iniciar la cámara:", err);
        textoEstado.innerText = "Error: No se pudo acceder a la cámara web.";
    });
}

encenderCamara();

let ultimoEnvio = 0;

function onResultsHands(results) {
    if (senaDetectada) return;

    if (results.multiHandLandmarks && results.multiHandLandmarks.length > 0) {
        const tiempoActual = Date.now();
        
        if (tiempoActual - ultimoEnvio > 1000) {
            ultimoEnvio = tiempoActual;
            const puntosMano = results.multiHandLandmarks[0];
            const tokenDinamico = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "";

            fetch('/api/reconocer-sena', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tokenDinamico
                },
                body: JSON.stringify({ puntos: puntosMano })
            })
            .then(res => {
                if (!res.ok) throw new Error('Error en la respuesta del servidor');
                return res.json();
            })
            .then(data => {
                if (data.success && data.id) {
                    senaDetectada = true; 
                    window.especialidadSeleccionadaId = data.id; 
                    
                    resultadoSena.innerText = `Especialidad detectada: ${data.nombre}`;
                    resultadoSena.style.backgroundColor = "#00bfa6"; 
                    resultadoSena.style.boxShadow = "0 0 15px rgba(0, 191, 166, 0.4)";
                    resultadoSena.style.transform = "scale(1.05)"; 
                    
                    textoEstado.innerText = "¡Especialidad fijada! Presione 'Escoja Horario' para continuar.";
                    textoEstado.style.color = "#00bfa6";
                    textoEstado.style.fontWeight = "bold";

                    if (streamCamara) {
                        streamCamara.getTracks().forEach(track => track.stop()); 
                    }
                    videoElement.style.opacity = "0.4"; 
                }
            })
            .catch(err => console.error("Error en la petición de señas:", err));
        }
    } else {
        if (!senaDetectada) {
            resultadoSena.innerText = "Esperando mano...";
            resultadoSena.style.backgroundColor = "#5372e7"; 
            resultadoSena.style.transform = "scale(1)";
            resultadoSena.style.boxShadow = "";
        }
    }
}

if (botonReintentar) {
    botonReintentar.addEventListener('click', function() {
        senaDetectada = false;
        window.especialidadSeleccionadaId = null;
        ultimoEnvio = 0;

        resultadoSena.innerText = "Esperando mano...";
        resultadoSena.style.backgroundColor = "#5372e7";
        resultadoSena.style.transform = "scale(1)";
        resultadoSena.style.boxShadow = "";

        if (streamCamara) {
            streamCamara.getTracks().forEach(track => track.stop());
        }

        videoElement.remove();
        encenderCamara();
    });
}

if (botonHorario) {
    botonHorario.addEventListener('click', function(e) {
        e.preventDefault();
        if (!window.especialidadSeleccionadaId) {
            alert("Por favor, realice la seña de su especialidad médica ante la cámara antes de continuar.");
            return;
        }
        window.location.href = `/reservar-cita?especialidad_id=${window.especialidadSeleccionadaId}`;
    });
}