<div wire:ignore class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-white rounded-xl border border-gray-200">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DNI del Doctor</label>
            <div class="flex items-center gap-2">
                <input type="text" id="dni" maxlength="8" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <span id="estado-dni" class="h-4 w-4 rounded-full bg-gray-200 inline-block"></span>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
            <div class="flex items-center gap-2">
                <input type="text" id="nombre" readonly class="w-full rounded-lg bg-gray-100 border-gray-300 text-gray-500 shadow-sm">
                <span id="estado-datos" class="h-4 w-4 rounded-full bg-gray-200 inline-block"></span>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
            <input type="text" id="fecha" readonly class="w-full rounded-lg bg-gray-100 border-gray-300 text-gray-500 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
            <div class="flex items-center gap-2">
                <input type="text" id="celular" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <span id="estado-celular" class="h-4 w-4 rounded-full bg-gray-200 inline-block"></span>
            </div>
        </div>
        <div class="pt-2 flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Validacion Biometrica:</span>
            <span id="estado-rostro" class="h-4 w-4 rounded-full bg-gray-200 inline-block"></span>
        </div>
        <div id="mensaje-escaneo" class="rounded-lg bg-sky-50 border border-sky-100 px-4 py-3 text-sm text-sky-900">
            Completa el DNI y celular. El escaneo iniciara automaticamente.
        </div>
    </div>

    <div class="flex flex-col justify-center items-center bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300">
        <div id="contenedor-video" class="relative w-full aspect-video bg-slate-900 rounded-lg overflow-hidden flex items-center justify-center">
            <div id="placeholder-face" class="text-center p-4 text-gray-400 text-sm">
                Complete el DNI y Celular para activar el escaneo facial automatico.
            </div>

            <div id="guia-rostro" class="hidden absolute inset-0 z-10 pointer-events-none">
                <div class="absolute inset-x-0 top-3 text-center text-xs font-semibold uppercase tracking-wide text-emerald-200 drop-shadow">
                    Centra tu rostro dentro del marco
                </div>
                <div class="absolute left-1/2 top-1/2 h-[72%] w-[46%] -translate-x-1/2 -translate-y-1/2 rounded-[50%] border-4 border-emerald-300/90 shadow-[0_0_0_999px_rgba(15,23,42,0.35)]"></div>
            </div>

            <div id="overlay-escaneo" class="hidden absolute inset-0 z-20 bg-slate-950/60 text-white flex flex-col items-center justify-center text-center px-6">
                <div id="texto-escaneo" class="text-sm font-semibold uppercase tracking-wide text-emerald-200">Preparate para el escaneo facial</div>
                <div id="contador-escaneo" class="text-7xl font-bold leading-none mt-2">10</div>
                <div id="ayuda-escaneo" class="mt-3 max-w-sm text-sm text-slate-100">
                    Mira la camara, ojos abiertos, sin anteojos y procura tener buena luz.
                </div>
            </div>

            <img id="preview-captura" class="hidden" alt="Vista previa del escaneo facial">
        </div>

        <div id="acciones-escaneo" class="mt-4 flex flex-wrap justify-center gap-3">
            <button id="confirmar-escaneo" type="button" class="hidden rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                Confirmar escaneo
            </button>
            <button id="repetir-escaneo" type="button" class="hidden rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50">
                Repetir captura
            </button>
        </div>
    </div>
</div>

<style>
    .correcto { background-color: #10b981 !important; box-shadow: 0 0 8px #10b981; }
    .error { background-color: #ef4444 !important; box-shadow: 0 0 8px #ef4444; }
    #contenedor-video {
        min-height: 260px;
    }
    #placeholder-face {
        position: relative;
        z-index: 1;
    }
    #video-registro {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
    }
    #preview-captura {
        position: absolute;
        inset: 0;
        z-index: 30;
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        max-height: none !important;
        object-fit: cover;
        transform: scaleX(-1);
    }
    #preview-captura.hidden {
        display: none !important;
    }

    #overlay-escaneo {
        background: rgba(2, 6, 23, 0.24) !important;
        transition: background 0.35s ease;
    }

    #overlay-escaneo:has(#contador-escaneo:empty) {
        background: rgba(2, 6, 23, 0.04) !important;
    }

    #overlay-escaneo:has(#contador-escaneo:empty) > * {
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    #acciones-escaneo {
        width: 100%;
        min-height: 46px;
        align-items: center;
    }

    #acciones-escaneo button {
        min-width: 150px;
        min-height: 42px;
        border: 2px solid transparent !important;
        border-radius: 9px !important;
        padding: 0.65rem 1rem !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    #acciones-escaneo button:not(.hidden):hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 12px rgba(15, 23, 42, 0.16);
    }

    #confirmar-escaneo:not(.hidden) {
        background: #059669 !important;
        border-color: #047857 !important;
        color: #ffffff !important;
    }

    #repetir-escaneo:not(.hidden) {
        background: #ffffff !important;
        border-color: #64748b !important;
        color: #0f172a !important;
    }

    #acciones-escaneo button:focus-visible {
        outline: 3px solid rgba(16, 185, 129, 0.35);
        outline-offset: 2px;
    }
</style>

<script src="{{ asset('js/face-api.js') }}"></script>

<script>
(function() {
    function arrancarLogica() {
        const dniInput = document.getElementById("dni");
        const celularInput = document.getElementById("celular");

        if (!dniInput || !celularInput) {
            setTimeout(arrancarLogica, 200);
            return;
        }

        const nombreInput = document.getElementById("nombre");
        const fechaInput = document.getElementById("fecha");
        const estadoDni = document.getElementById("estado-dni");
        const estadoDatos = document.getElementById("estado-datos");
        const estadoCelular = document.getElementById("estado-celular");
        const estadoRostro = document.getElementById("estado-rostro");
        const contenedor = document.getElementById("contenedor-video");
        const placeholder = document.getElementById("placeholder-face");
        const guiaRostro = document.getElementById("guia-rostro");
        const overlayEscaneo = document.getElementById("overlay-escaneo");
        const textoEscaneo = document.getElementById("texto-escaneo");
        const contadorEscaneo = document.getElementById("contador-escaneo");
        const ayudaEscaneo = document.getElementById("ayuda-escaneo");
        const previewCaptura = document.getElementById("preview-captura");
        const confirmarEscaneo = document.getElementById("confirmar-escaneo");
        const repetirEscaneo = document.getElementById("repetir-escaneo");
        const mensajeEscaneo = document.getElementById("mensaje-escaneo");

        let modelsLoaded = false;
        let dniValido = false;
        let datosValidos = false;
        let celularValido = false;
        let camaraStream = null;
        let cuentaRegresiva = null;
        let busquedaRostro = null;
        let descriptorPendiente = null;
        let escaneoActivo = false;
        let escaneoConfirmado = false;
        let inicioProgramado = null;

        function marcarEstado(elemento, estado) {
            if (!elemento) return;
            elemento.classList.remove("correcto", "error");
            if (estado === "correcto") elemento.classList.add("correcto");
            if (estado === "error") elemento.classList.add("error");
        }

        function mostrarMensaje(texto, tipo = "info") {
            if (!mensajeEscaneo) return;
            mensajeEscaneo.textContent = texto;
            mensajeEscaneo.className = "rounded-lg px-4 py-3 text-sm border";

            if (tipo === "ok") {
                mensajeEscaneo.classList.add("bg-emerald-50", "border-emerald-100", "text-emerald-900");
            } else if (tipo === "error") {
                mensajeEscaneo.classList.add("bg-red-50", "border-red-100", "text-red-900");
            } else {
                mensajeEscaneo.classList.add("bg-sky-50", "border-sky-100", "text-sky-900");
            }
        }

        function mostrarBotones(modo) {
            confirmarEscaneo.classList.toggle("hidden", modo !== "confirmar");
            repetirEscaneo.classList.toggle("hidden", modo !== "repetir" && modo !== "confirmar");
        }

        function apagarHardwareCamara() {
            if (camaraStream) {
                camaraStream.getTracks().forEach(track => track.stop());
                camaraStream = null;
            }
        }

        function limpiarTemporizadores() {
            if (cuentaRegresiva) {
                clearInterval(cuentaRegresiva);
                cuentaRegresiva = null;
            }
            if (busquedaRostro) {
                clearInterval(busquedaRostro);
                busquedaRostro = null;
            }
            if (inicioProgramado) {
                clearTimeout(inicioProgramado);
                inicioProgramado = null;
            }
        }

        function limpiarVideoAnterior() {
            const videoAnterior = document.getElementById("video-registro");
            if (videoAnterior) videoAnterior.remove();
        }

        function resetearEscaneo() {
            limpiarTemporizadores();
            apagarHardwareCamara();
            limpiarVideoAnterior();

            escaneoActivo = false;
            escaneoConfirmado = false;
            descriptorPendiente = null;

            placeholder.style.display = "block";
            guiaRostro.classList.add("hidden");
            overlayEscaneo.classList.add("hidden");
            previewCaptura.classList.add("hidden");
            previewCaptura.removeAttribute("src");
            marcarEstado(estadoRostro, "");
            mostrarBotones("ninguno");
            @this.set("data.face_vector", null, false);
        }

        function requisitosListos() {
            return dniValido && datosValidos && celularValido && modelsLoaded;
        }

        function actualizarDisponibilidadEscaneo() {
            if (requisitosListos() && !escaneoActivo && !escaneoConfirmado) {
                mostrarBotones("ninguno");
                placeholder.textContent = "Listo. El escaneo iniciara automaticamente.";
                placeholder.style.display = "block";
                mostrarMensaje("Todo listo. El escaneo iniciara automaticamente; prepara al doctor frente a la camara.", "ok");

                if (!inicioProgramado) {
                    inicioProgramado = setTimeout(() => {
                        inicioProgramado = null;
                        iniciarCamara();
                    }, 700);
                }
                return;
            }

            if (!modelsLoaded) {
                mostrarBotones("ninguno");
                return;
            }

            if (!escaneoConfirmado) {
                mostrarBotones("ninguno");
            }
        }

        dniInput.addEventListener("input", function () {
            const valorDni = dniInput.value.trim();
            nombreInput.value = "";
            fechaInput.value = "";
            dniValido = false;
            datosValidos = false;
            resetearEscaneo();
            marcarEstado(estadoDni, "");
            marcarEstado(estadoDatos, "");
            mostrarMensaje("Completa el DNI y celular. El escaneo iniciara automaticamente.", "info");

            if (valorDni.length === 8 && !isNaN(valorDni)) {
                dniValido = true;
                marcarEstado(estadoDni, "correcto");

                fetch(`/api/consultar-dni/${valorDni}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            nombreInput.value = data.nombre;
                            fechaInput.value = data.fecha;
                            datosValidos = true;
                            marcarEstado(estadoDatos, "correcto");
                            @this.set("data.nombre", data.nombre, false);
                            @this.set("data.dni", valorDni, false);
                            actualizarDisponibilidadEscaneo();
                        } else {
                            marcarEstado(estadoDatos, "error");
                            mostrarMensaje("No se encontraron datos para ese DNI.", "error");
                        }
                    })
                    .catch(error => {
                        console.error("Error al consultar DNI:", error);
                        marcarEstado(estadoDatos, "error");
                        mostrarMensaje("No se pudo consultar el DNI. Revisa la conexion o intenta otra vez.", "error");
                    });
            } else if (valorDni.length > 0) {
                marcarEstado(estadoDni, "error");
            }
        });

        celularInput.addEventListener("input", function () {
            const valorCelular = celularInput.value.trim();
            const celularRegEx = /^(\+51\s?)?9\d{8}$/.test(valorCelular);
            resetearEscaneo();

            if (valorCelular.length === 0) {
                celularValido = false;
                marcarEstado(estadoCelular, "");
                return;
            }

            if (celularRegEx) {
                celularValido = true;
                marcarEstado(estadoCelular, "correcto");
                @this.set("data.celular", valorCelular, false);
                actualizarDisponibilidadEscaneo();
            } else {
                celularValido = false;
                marcarEstado(estadoCelular, "error");
                mostrarMensaje("El celular debe empezar con 9 y tener 9 digitos.", "error");
            }
        });

        async function loadModels() {
            if (typeof faceapi === "undefined") {
                setTimeout(loadModels, 200);
                return;
            }

            try {
                mostrarMensaje("Cargando modelos de reconocimiento facial...", "info");
                const MODEL_URL = "https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/";
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                modelsLoaded = true;
                mostrarMensaje("Modelos listos. Completa DNI y celular para activar el escaneo automatico.", "ok");
                actualizarDisponibilidadEscaneo();
            } catch (error) {
                console.error("Error al descargar modelos Face-API:", error);
                mostrarMensaje("No se pudieron cargar los modelos faciales. Revisa tu internet y recarga la pagina.", "error");
            }
        }
        loadModels();

        async function iniciarCamara() {
            if (!requisitosListos() || escaneoActivo) return;

            escaneoActivo = true;
            escaneoConfirmado = false;
            descriptorPendiente = null;
            marcarEstado(estadoRostro, "");
            mostrarBotones("ninguno");
            placeholder.style.display = "none";
            previewCaptura.classList.add("hidden");
            mostrarMensaje("Abriendo camara. Tendras 5 segundos para centrar el rostro antes de capturar.", "info");

            try {
                limpiarVideoAnterior();
                apagarHardwareCamara();

                const video = document.createElement("video");
                video.id = "video-registro";
                video.autoplay = true;
                video.muted = true;
                video.playsInline = true;

                camaraStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: "user"
                    }
                });

                video.srcObject = camaraStream;
                contenedor.appendChild(video);
                guiaRostro.classList.remove("hidden");

                video.addEventListener("canplay", () => {
                    iniciarCuentaRegresiva(video);
                }, { once: true });
            } catch (err) {
                console.error("No se pudo acceder a la camara:", err);
                escaneoActivo = false;
                marcarEstado(estadoRostro, "error");
                placeholder.style.display = "block";
                guiaRostro.classList.add("hidden");
                mostrarBotones("repetir");
                mostrarMensaje("No se pudo abrir la camara. Dale permiso al navegador y vuelve a intentar.", "error");
            }
        }

        function iniciarCuentaRegresiva(video) {
            let segundos = 5;
            overlayEscaneo.classList.remove("hidden");
            textoEscaneo.textContent = "Preparate para el escaneo facial";
            ayudaEscaneo.textContent = "Mira la camara, ojos abiertos, sin anteojos y sin moverte.";
            contadorEscaneo.textContent = segundos;

            cuentaRegresiva = setInterval(() => {
                if (segundos === 1) {
                    limpiarTemporizadores();
                    textoEscaneo.textContent = "Analizando rostro...";
                    contadorEscaneo.textContent = "";
                    ayudaEscaneo.textContent = "Mantente quieto, mira la camara y permanece sin anteojos.";
                    buscarRostroHastaCapturar(video);
                    return;
                }

                segundos -= 1;
                contadorEscaneo.textContent = segundos;
            }, 1000);
        }

        function buscarRostroHastaCapturar(video) {
            let intentos = 0;
            const maxIntentos = 40;

            busquedaRostro = setInterval(async () => {
                intentos += 1;

                try {
                    const detection = await detectarRostro(video);

                    if (detection) {
                        limpiarTemporizadores();
                        finalizarCaptura(video, detection);
                        return;
                    }

                    ayudaEscaneo.textContent = intentos % 2 === 0
                        ? "No te alejes. Centra la cara dentro del marco verde."
                        : "Buscando rostro claro... mejora la luz y mira directo a la camara.";

                    if (intentos >= maxIntentos) {
                        limpiarTemporizadores();
                        overlayEscaneo.classList.add("hidden");
                        mostrarBotones("repetir");
                        marcarEstado(estadoRostro, "error");
                        escaneoActivo = false;
                        mostrarMensaje("No se logro una lectura clara. Ajusta luz, distancia y repite la captura.", "error");
                    }
                } catch (error) {
                    limpiarTemporizadores();
                    console.error("Error al analizar rostro:", error);
                    overlayEscaneo.classList.add("hidden");
                    mostrarBotones("repetir");
                    marcarEstado(estadoRostro, "error");
                    escaneoActivo = false;
                    mostrarMensaje("Ocurrio un error al analizar el rostro. Repite la captura.", "error");
                }
            }, 500);
        }

        async function detectarRostro(video) {
            const opcionesAmplias = new faceapi.TinyFaceDetectorOptions({
                inputSize: 608,
                scoreThreshold: 0.30
            });

            const detection = await faceapi.detectSingleFace(video, opcionesAmplias)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) return null;

            const box = detection.detection.box;
            const areaRostro = box.width * box.height;
            const areaVideo = video.videoWidth * video.videoHeight;
            const rostroSuficiente = areaRostro / areaVideo > 0.025;

            return rostroSuficiente ? detection : null;
        }

        function finalizarCaptura(video, detection) {
            descriptorPendiente = Array.from(detection.descriptor);
            dibujarVistaPrevia(video);
            apagarHardwareCamara();
            limpiarVideoAnterior();

            overlayEscaneo.classList.add("hidden");
            guiaRostro.classList.add("hidden");
            previewCaptura.classList.remove("hidden");
            mostrarBotones("confirmar");
            escaneoActivo = false;
            mostrarMensaje("Captura lista. Revisa la imagen y confirma el escaneo si esta bien.", "info");
            console.log("Rostro capturado. Esperando confirmacion del usuario.");
        }

        function dibujarVistaPrevia(video) {
            const canvas = document.createElement("canvas");
            const rect = contenedor.getBoundingClientRect();
            canvas.width = Math.max(640, Math.round(rect.width));
            canvas.height = Math.max(360, Math.round(rect.height));

            const ctx = canvas.getContext("2d");
            const videoRatio = video.videoWidth / video.videoHeight;
            const canvasRatio = canvas.width / canvas.height;
            let sx = 0;
            let sy = 0;
            let sw = video.videoWidth;
            let sh = video.videoHeight;

            if (videoRatio > canvasRatio) {
                sw = video.videoHeight * canvasRatio;
                sx = (video.videoWidth - sw) / 2;
            } else {
                sh = video.videoWidth / canvasRatio;
                sy = (video.videoHeight - sh) / 2;
            }

            ctx.drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
            previewCaptura.src = canvas.toDataURL("image/jpeg", 0.92);
        }

        confirmarEscaneo.addEventListener("click", function () {
            if (!descriptorPendiente) return;

            @this.set("data.face_vector", descriptorPendiente, false);
            escaneoConfirmado = true;
            escaneoActivo = false;
            marcarEstado(estadoRostro, "correcto");
            mostrarBotones("ninguno");
            mostrarMensaje("Escaneo confirmado. Ahora puedes crear el doctor.", "ok");
            console.log("Rostro validado con exito y confirmado por el usuario.");
        });

        repetirEscaneo.addEventListener("click", function () {
            resetearEscaneo();
            mostrarMensaje("Preparando una nueva captura automatica. Centra el rostro frente a la camara.", "info");
            actualizarDisponibilidadEscaneo();
        });
    }

    arrancarLogica();
})();
</script>
