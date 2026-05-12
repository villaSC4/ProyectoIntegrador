const btnEscanear = document.getElementById('btnEscanear');
const contenedor = document.getElementById('contenedor-video-login');
const placeholder = document.getElementById('placeholder-login');

async function cargarModelos() {
    const MODEL_URL = 'https://raw.githubusercontent.com/vladmandic/face-api/master/model/';
    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    console.log("Modelos de Login listos");
}
cargarModelos();

btnEscanear.addEventListener('click', async () => {
    const video = document.createElement('video');
    video.autoplay = true;
    video.muted = true;
    video.style.width = "100%";
    video.style.borderRadius = "10px";

    placeholder.style.display = 'none';
    contenedor.appendChild(video);

    const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
    video.srcObject = stream;

    video.addEventListener('play', () => {
        const interval = setInterval(async () => {
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection) {
                const descriptorActual = Array.from(detection.descriptor);
                
                // Enviar a Laravel para comparar
                verificarIdentidad(descriptorActual, stream, interval);
                clearInterval(interval);
            }
        }, 1000);
    });
});

function verificarIdentidad(descriptor, stream, interval) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
            return res.text().then(text => { throw new Error(text) });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            alert("Bienvenido " + data.usuario);
            stream.getTracks().forEach(track => track.stop());
            window.location.href = "/home";
        } else {
            alert("Rostro no reconocido.");
            location.reload();
        }
    })
    .catch(err => {
        console.error("Error detallado:", err);
        alert("Error de comunicación con el servidor.");
    });
}