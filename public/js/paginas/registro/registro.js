const dniInput = document.getElementById("dni");
const nombreInput = document.getElementById("nombre");
const fechaInput = document.getElementById("fecha");
const celularInput = document.getElementById("celular");

const estadoDni = document.getElementById("estado-dni");
const estadoDatos = document.getElementById("estado-datos");
const estadoCelular = document.getElementById("estado-celular");
const estadoRostro = document.getElementById("estado-rostro");

const contenedor = document.getElementById("contenedor-video");
const placeholder = document.getElementById("placeholder-face");
const mensajePreview = document.getElementById("mensaje-preview-registro");

let modelsLoaded = false;
let camaraStream = null;
let detectorInterval = null;
let descriptorRegistrado = null;
let cuentaRegresivaId = null;
let tiempoMaximoId = null;
let escaneoIniciado = false;

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
        const MODEL_URL = "https://raw.githubusercontent.com/vladmandic/face-api/master/model/";
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        modelsLoaded = true;
        console.log("MediSign: modelos de registro listos");
        verificarRequisitosParaIA();
    } catch (error) {
        console.error("Error al descargar modelos Face-API:", error);
    }
}

loadModels();

function verificarRequisitosParaIA() {
    const dniOk = estadoDni?.classList.contains("correcto");
    const datosOk = estadoDatos?.classList.contains("correcto");
    const celularOk = estadoCelular?.classList.contains("correcto");

    if (dniOk && datosOk && celularOk && modelsLoaded) {
        iniciarEscaneoFacial();
    }
}

async function iniciarEscaneoFacial() {
    if (document.getElementById("video-registro") || escaneoIniciado) return;

    escaneoIniciado = true;
    descriptorRegistrado = null;
    marcarEstado(estadoRostro, "");

    try {
        const video = document.createElement("video");
        video.id = "video-registro";
        video.autoplay = true;
        video.muted = true;
        video.playsInline = true;

        camaraStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: "user"
            },
            audio: false
        });

        video.srcObject = camaraStream;
        placeholder.style.display = "none";
        contenedor.innerHTML = "";
        contenedor.appendChild(video);

        video.addEventListener("play", () => iniciarCuentaRegresiva(video), { once: true });
    } catch (error) {
        console.error("No se pudo acceder a la camara:", error);
        escaneoIniciado = false;
        apagarHardwareCamara();
        restaurarPlaceholder("No se pudo abrir la camara. Revisa el permiso del navegador.");
    }
}

function crearOverlayEscaneo() {
    let overlay = document.getElementById("overlay-escaneo-registro");
    if (overlay) return overlay;

    overlay = document.createElement("div");
    overlay.id = "overlay-escaneo-registro";
    overlay.className = "overlay-escaneo-registro visible";
    overlay.innerHTML = `
        <strong id="titulo-escaneo-registro">Preparate para el escaneo facial</strong>
        <b id="contador-escaneo-registro">5</b>
        <span id="ayuda-escaneo-registro">Mira la camara, mantente quieto y quitate los anteojos.</span>
    `;
    contenedor.appendChild(overlay);
    return overlay;
}

function iniciarCuentaRegresiva(video) {
    const overlay = crearOverlayEscaneo();
    const titulo = document.getElementById("titulo-escaneo-registro");
    const contador = document.getElementById("contador-escaneo-registro");
    const ayuda = document.getElementById("ayuda-escaneo-registro");
    let segundos = 5;

    titulo.textContent = "Preparate para el escaneo facial";
    contador.textContent = segundos;
    ayuda.textContent = "Mira la camara, ojos abiertos, sin anteojos y sin moverte.";
    overlay.classList.add("visible");

    cuentaRegresivaId = setInterval(() => {
        if (segundos === 1) {
            clearInterval(cuentaRegresivaId);
            cuentaRegresivaId = null;
            contador.textContent = "";
            iniciarAnalisisRostro(video, titulo, ayuda);
            return;
        }

        segundos -= 1;
        contador.textContent = segundos;
    }, 1000);
}

function iniciarAnalisisRostro(video, titulo, ayuda) {
    titulo.textContent = "Analizando rostro...";
    ayuda.textContent = "Manten la cara centrada, mira directo a la camara y permanece sin anteojos.";

    let capturando = false;
    const opcionesDetector = new faceapi.TinyFaceDetectorOptions({
        inputSize: 416,
        scoreThreshold: 0.45
    });

    detectorInterval = setInterval(async () => {
        if (capturando) return;
        capturando = true;

        try {
            const detection = await faceapi.detectSingleFace(video, opcionesDetector)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                capturando = false;
                return;
            }

            clearInterval(detectorInterval);
            detectorInterval = null;
            clearTimeout(tiempoMaximoId);
            tiempoMaximoId = null;

            marcarEstado(estadoRostro, "correcto");
            descriptorRegistrado = Array.from(detection.descriptor);
            await mostrarVistaPrevia(video);
            apagarHardwareCamara();
            video.remove();
            document.getElementById("overlay-escaneo-registro")?.remove();
            escaneoIniciado = false;
            console.log("Rostro validado con exito");
        } catch (error) {
            capturando = false;
            console.error("Error al analizar el rostro:", error);
        }
    }, 800);

    tiempoMaximoId = setTimeout(() => {
        if (detectorInterval) {
            clearInterval(detectorInterval);
            detectorInterval = null;
        }

        apagarHardwareCamara();
        escaneoIniciado = false;
        marcarEstado(estadoRostro, "error");
        titulo.textContent = "No se detecto el rostro";
        ayuda.textContent = "Quitate los anteojos, mejora la luz y vuelve a completar los datos.";
        restaurarPlaceholder("No se detecto un rostro claro. Vuelve a completar los datos para repetir.");
        alert("No se detecto un rostro claro. Quitate los anteojos, centra la cara y vuelve a intentarlo.");
    }, 12000);
}

function restaurarPlaceholder(texto) {
    if (detectorInterval) {
        clearInterval(detectorInterval);
        detectorInterval = null;
    }
    if (cuentaRegresivaId) {
        clearInterval(cuentaRegresivaId);
        cuentaRegresivaId = null;
    }
    if (tiempoMaximoId) {
        clearTimeout(tiempoMaximoId);
        tiempoMaximoId = null;
    }

    contenedor.innerHTML = "";
    if (mensajePreview) mensajePreview.classList.remove("visible");
    placeholder.textContent = texto;
    placeholder.style.display = "block";
    contenedor.appendChild(placeholder);
}

async function mostrarVistaPrevia(video) {
    if (typeof video.requestVideoFrameCallback === "function") {
        await new Promise(resolve => video.requestVideoFrameCallback(() => resolve()));
    } else {
        await new Promise(resolve => requestAnimationFrame(resolve));
    }

    document.querySelectorAll("#preview-registro").forEach(elemento => elemento.remove());

    const canvas = document.createElement("canvas");
    const ancho = Math.max(contenedor.clientWidth, 640);
    const alto = Math.max(contenedor.clientHeight, 480);
    let fuente = video;
    let fuenteAncho = video.videoWidth || 640;
    let fuenteAlto = video.videoHeight || 480;

    const pistaVideo = camaraStream?.getVideoTracks?.()[0];
    if (pistaVideo && typeof ImageCapture !== "undefined") {
        try {
            const captura = await new ImageCapture(pistaVideo).grabFrame();
            fuente = captura;
            fuenteAncho = captura.width;
            fuenteAlto = captura.height;
        } catch (error) {
            console.warn("No se pudo capturar el frame directo; se usara el video.", error);
        }
    }

    const videoAncho = fuenteAncho;
    const videoAlto = fuenteAlto;
    const escalaVideo = videoAncho / videoAlto;
    const escalaCanvas = ancho / alto;
    let sx = 0;
    let sy = 0;
    let sw = videoAncho;
    let sh = videoAlto;

    if (escalaVideo > escalaCanvas) {
        sw = videoAlto * escalaCanvas;
        sx = (videoAncho - sw) / 2;
    } else {
        sh = videoAncho / escalaCanvas;
        sy = (videoAlto - sh) / 2;
    }

    canvas.width = ancho;
    canvas.height = alto;
    const contexto = canvas.getContext("2d");
    contexto.fillStyle = "#111827";
    contexto.fillRect(0, 0, ancho, alto);
    contexto.drawImage(fuente, sx, sy, sw, sh, 0, 0, ancho, alto);

    if (fuente !== video && typeof fuente.close === "function") {
        fuente.close();
    }

    const preview = document.createElement("img");
    preview.id = "preview-registro";
    preview.src = canvas.toDataURL("image/jpeg", 0.92);
    preview.alt = "Vista previa del rostro capturado";
    contenedor.appendChild(preview);
    if (mensajePreview) mensajePreview.classList.add("visible");
}

document.getElementById("btnTerminar").addEventListener("click", function () {
    const dni = dniInput.value.trim();
    const cel = celularInput.value.trim();
    const nombre = nombreInput.value.trim();
    const rostroOk = estadoRostro?.classList.contains("correcto");

    if (!dni || !cel || !nombre || !rostroOk || !descriptorRegistrado) {
        alert("Por favor, completa todos los campos e incluye el escaneo facial antes de terminar.");
        return;
    }

    const csrfToken = document.querySelector('input[name="_token"]')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    if (!csrfToken) {
        alert("Error de seguridad: token CSRF no localizado.");
        return;
    }

    fetch("/api/finalizar-registro", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            dni,
            nombre,
            celular: cel,
            face_vector: descriptorRegistrado,
            _token: csrfToken
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Registro exitoso. Tu perfil biometrico ha sido guardado. Ya puedes iniciar sesion.");
                window.location.href = "/";
            } else {
                alert("Error al registrar en MediSign: " + data.message);
                location.reload();
            }
        })
        .catch(error => {
            console.error("Error en el registro:", error);
            alert("No se pudo conectar con el servidor de Laravel.");
        });
});

function apagarHardwareCamara() {
    if (camaraStream) {
        camaraStream.getTracks().forEach(track => track.stop());
        camaraStream = null;
    }
}
