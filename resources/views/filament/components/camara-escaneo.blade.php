
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-white rounded-xl border border-gray-200">
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
            <span class="text-sm font-medium text-gray-700">Validación Biométrica:</span>
            <span id="estado-rostro" class="h-4 w-4 rounded-full bg-gray-200 inline-block"></span>
        </div>
    </div>

    <div class="flex flex-col justify-center items-center bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300">
        <div id="contenedor-video" class="relative w-full aspect-video bg-slate-900 rounded-lg overflow-hidden flex items-center justify-center">
            <div id="placeholder-face" class="text-center p-4 text-gray-400 text-sm">
                Complete el DNI y Celular para activar el escaneo facial.
            </div>
        </div>
    </div>
</div>

<style>
    .correcto { background-color: #10b981 !important; box-shadow: 0 0 8px #10b981; }
    .error { background-color: #ef4444 !important; box-shadow: 0 0 8px #ef4444; }
</style>

<script src="{{ asset('js/face-api.js') }}"></script>

<script>
(function() {
    function arrancarLógica() {
        const dniInput = document.getElementById("dni");
        const celularInput = document.getElementById("celular");

        if (!dniInput || !celularInput) {
            setTimeout(arrancarLógica, 200);
            return;
        }

        const nombreInput = document.getElementById("nombre");
        const fechaInput = document.getElementById("fecha");
        const estadoDni = document.getElementById("estado-dni");
        const estadoDatos = document.getElementById("estado-datos");
        const estadoCelular = document.getElementById("estado-celular");
        const estadoRostro = document.getElementById('estado-rostro');
        const contenedor = document.getElementById('contenedor-video');
        const placeholder = document.getElementById('placeholder-face');

        let modelsLoaded = false;
        let dniValido = false;
        let datosValidos = false;
        let celularValido = false;
        
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
            dniValido = false;
            datosValidos = false;
            marcarEstado(estadoDni, "");
            marcarEstado(estadoDatos, "");

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
                            
                            @this.set('data.nombre', data.nombre); 
                            
                            @this.set('data.dni', valorDni, true);

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
            const celularRegEx = /^(\+51\s?)?9\d{8}$/.test(valorCelular);

            if (valorCelular.length === 0) {
                celularValido = false;
                marcarEstado(estadoCelular, "");
                return;
            }
            
            if (celularRegEx) {
                celularValido = true;
                marcarEstado(estadoCelular, "correcto");
                @this.set('data.celular', valorCelular, true);
                verificarRequisitosParaIA(); 
            } else {
                celularValido = false;
                marcarEstado(estadoCelular, "error");
            }
        });

        async function loadModels() {
            if (typeof faceapi === 'undefined') {
                setTimeout(loadModels, 200);
                return;
            }

            try {
                const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                
                modelsLoaded = true;
                verificarRequisitosParaIA();
            } catch (error) {
                console.error("Error al descargar modelos Face-API:", error);
            }
        }
        loadModels();

        function verificarRequisitosParaIA() {
            if (dniValido && datosValidos && celularValido && modelsLoaded) {
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
                            
                            @this.set('data.face_vector', descriptorRegistrado, true);
                            
                            apagarHardwareCamara();
                        }
                    }, 800);
                });
            } catch (err) {
                console.error("No se pudo acceder a la cámara:", err);
            }
        }

        function apagarHardwareCamara() {
            if (camaraStream) {
                camaraStream.getTracks().forEach(track => track.stop());
                camaraStream = null;
            }
        }
    }

    arrancarLógica();
})();
</script>