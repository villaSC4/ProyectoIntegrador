// ==================== ELEMENTOS DEL DOM DEL TRADUCTOR ====================
const salida = document.querySelector("[data-traduccion]");
const estadoCamara = document.querySelector("[data-estado-camara]");
const etiquetaCamara = document.querySelector("[data-etiqueta-camara]");
const botonToggleCamara = document.querySelector("[data-toggle-camara]");
const botonEnfocarCamara = document.querySelector("[data-enfocar-camara]");
const videoSenas = document.querySelector("[data-video-senas]");
const canvasSenas = document.querySelector("[data-canvas-senas]");
const mostrarPuntosManos = canvasSenas?.dataset.mostrarPuntos !== "false";
const guiaCamara = document.querySelector(".camara-guia");
const contextoCalidad = document.querySelector("[data-contexto-calidad]");
const resultadoEnVivo = document.querySelector("[data-resultado-en-vivo]");
const coincidenciaEnVivo = document.querySelector("[data-coincidencia-en-vivo]");
const estabilidadEnVivo = document.querySelector("[data-estabilidad-en-vivo]");
const botonVozAuto = document.querySelector("[data-voz-auto]");
const botonPausarVoz = document.querySelector("[data-pausar-voz]");
const botonReiniciarVoz = document.querySelector("[data-reiniciar-voz]");
const estadoVoz = document.querySelector("[data-estado-voz]");
const botonDeshacerTranscripcion = document.querySelector(
    "[data-deshacer-transcripcion]",
);
const botonLimpiarTranscripcion = document.querySelector(
    "[data-limpiar-transcripcion]",
);
const contadorTranscripcion = document.querySelector(
    "[data-contador-transcripcion]",
);
const formDoctor = document.querySelector("[data-form-doctor]");
const mensajeDoctor = document.querySelector("[data-mensaje-doctor]");
const ultimoMensaje = document.querySelector("[data-ultimo-mensaje]");
const botonMicrofono = document.querySelector("[data-microfono]");
const botonPantallaPaciente = document.querySelector(
    "[data-pantalla-paciente]",
);
const estadoEntrenamiento = document.querySelector(
    "[data-estado-entrenamiento]",
);
const botonesEntrenamiento = document.querySelectorAll("[data-entrenar-sena]");
const panelValidacionSena = document.querySelector(
    "[data-panel-validacion-sena]",
);
const textoPrediccionSena = document.querySelector("[data-prediccion-sena]");
const botonConfirmarSena = document.querySelector("[data-confirmar-sena]");
const botonCorregirSena = document.querySelector("[data-corregir-sena]");
const botonNingunaSena = document.querySelector("[data-ninguna-sena]");
const botonDescartarSena = document.querySelector("[data-descartar-sena]");
const contextoMano = document.querySelector("[data-contexto-mano]");
const contextoRostro = document.querySelector("[data-contexto-rostro]");
const contextoTorso = document.querySelector("[data-contexto-torso]");
const fraseEntrenamiento = document.querySelector("[data-frase-entrenamiento]");
const botonIniciarGrabacion = document.querySelector(
    "[data-iniciar-grabacion]",
);
const botonGuardarFraseEntrenada = document.querySelector(
    "[data-guardar-frase-entrenada]",
);
const panelEntrenamientoLibre = document.querySelector(".entrenamiento-libre");
const botonToggleEntrenamiento = document.querySelector(
    "[data-toggle-entrenamiento]",
);
const panelEntrenamiento = document.querySelector("[data-panel-entrenamiento]");
const panelCorreccion = document.querySelector("[data-panel-correccion]");
const fraseCorreccion = document.querySelector("[data-frase-correccion]");
const botonGuardarCorreccion = document.querySelector(
    "[data-guardar-correccion]",
);
const contenedorFrasesAprendidas = document.querySelector(
    "[data-frases-aprendidas]",
);
const opcionesFrases = document.querySelector("[data-opciones-frases]");

// ==================== ESTADO DE LA PANTALLA EXTERNA Y MICROFONO ====================
let ventanaPaciente = null;
let microfonoActivo = false;
let canalPaciente = null;
let controladorCamara = null;
let camaraActiva = false;
let cambioCamaraEnCurso = false;
let generacionCamara = 0;
let textoReconocido = "";
let fragmentosReconocidos = [];
let ultimaSenaReconocida = "";
let ultimaSenaReconocidaEn = 0;
let ultimoFrameConMano = 0;
let ultimosPuntosMano = null;
let ultimasManos = [];
let ultimaPrediccionSena = null;
let temporizadorCapturaPrediccion = null;
let reconocimientosActivos = 0;
let siguienteSolicitudReconocimiento = 1;
let siguienteResultadoReconocimiento = 1;
let resultadosReconocimientoPendientes = new Map();
let ultimoAnalisisRostro = 0;
let ultimoAnalisisTorso = 0;
let ultimoRostroVisibleEn = 0;
let ultimoCuerpoVisibleEn = 0;
let historialManos = [];
let historialCabeza = [];
let grabandoFrase = false;
let secuenciaGrabada = [];
let temporizadorBusquedaFrases = null;
let cabezaActual = null;
let rostroActual = null;
let cuerpoActual = null;
let ultimoGestoCabeza = 0;
let ultimaEmisionPorCodigo = new Map();
let cuentaRegresivaGrabacion = null;
let memoriaManos = new Map();
let vozAutomaticaActiva = false;
let vozPausada = false;
let colaVoz = [];
let vozLeyendo = false;
let colaSegmentosReconocimiento = [];
let segmentoEnCurso = null;
let ultimoFrameSegmentador = null;
let inicioAusenciaManos = 0;
let esperandoCambioParaNuevaSena = false;
let ultimoCodigoConfirmado = "";
let permiteRepetirUltimaSena = true;
let capturandoCorreccion = false;
let secuenciaCorreccion = [];
let correccionVioManos = false;
let ultimoFrameCorreccionConManos = 0;
let reconocimientoPausadoPorEntrenamiento = false;

const RETENCION_MANO_OCULTA_MS = 420;
const DURACION_MINIMA_SENA_MS = 240;
const DURACION_MAXIMA_SENA_MS = 1600;
const PAUSA_CIERRE_SENA_MS = 140;
const UMBRAL_MOVIMIENTO_SENA = 0.045;
const FRAMES_CONFIRMACION_MANO = 2;
const CONFIANZA_MINIMA_MANO = 0.46;
const TAMANO_MINIMO_MANO = 0.022;
const TAMANO_MINIMO_MANO_LEJANA = 0.012;
const CONFIANZA_MINIMA_MANO_LEJANA = 0.58;
const CONTEXTO_CORPORAL_VIGENTE_MS = 1400;
const TAMANO_MAXIMO_MANO = 0.50;
const DIFERENCIA_MAXIMA_FORMA_MANO = 0.42;
const AUSENCIA_REINICIA_REPETICION_MS = 900;
const MAX_SEGMENTOS_EN_COLA = 12;
const MAX_RECONOCIMIENTOS_CONCURRENTES = 1;
const TIMEOUT_RECONOCIMIENTO_MS = 7000;
const COOLDOWN_MISMA_SENA_MS = 3000;
const COOLDOWN_EMISION_CODIGO_MS = 3000;
const VENTANA_CONFIRMACION_ENTRENAMIENTO_MS = 20000;
const CODIGO_NINGUNA_SENA = "__ninguna_sena__";

const CONEXIONES_MANO = [
    [0, 1], [1, 2], [2, 3], [3, 4],
    [0, 5], [5, 6], [6, 7], [7, 8],
    [5, 9], [9, 10], [10, 11], [11, 12],
    [9, 13], [13, 14], [14, 15], [15, 16],
    [13, 17], [0, 17], [17, 18], [18, 19], [19, 20],
];

if ("BroadcastChannel" in window) {
    canalPaciente = new BroadcastChannel("medisign-paciente");
}

// ==================== VOZ: TEXTO RECONOCIDO HACIA AUDIO ====================
const vozDisponible = () => "speechSynthesis" in window && "SpeechSynthesisUtterance" in window;

const actualizarControlesVoz = () => {
    const disponible = vozDisponible();

    if (botonVozAuto) {
        botonVozAuto.disabled = !disponible;
        botonVozAuto.dataset.activo = String(vozAutomaticaActiva);
        botonVozAuto.textContent = vozAutomaticaActiva
            ? "Voz auto: si"
            : "Voz auto: no";
    }

    if (botonPausarVoz) {
        botonPausarVoz.disabled = !disponible || (!vozLeyendo && colaVoz.length === 0);
        botonPausarVoz.textContent = vozPausada ? "Reanudar voz" : "Pausar voz";
    }

    if (botonReiniciarVoz) {
        botonReiniciarVoz.disabled = !disponible || fragmentosReconocidos.length === 0;
    }

    if (!estadoVoz) return;
    if (!disponible) {
        estadoVoz.textContent = "Voz no disponible en este navegador.";
        estadoVoz.dataset.estado = "error";
        return;
    }

    if (vozLeyendo) {
        estadoVoz.textContent = vozPausada
            ? "Lectura pausada."
            : "Leyendo texto reconocido.";
        estadoVoz.dataset.estado = "ok";
        return;
    }

    estadoVoz.textContent = vozAutomaticaActiva
        ? "Voz automatica activada. Cada fragmento nuevo se leera al confirmarse."
        : "Voz automatica desactivada.";
    estadoVoz.dataset.estado = vozAutomaticaActiva ? "ok" : "";
};

const crearLectura = (texto, alFinalizar = null) => {
    const voz = new SpeechSynthesisUtterance(texto);
    voz.lang = "es-PE";
    voz.rate = 0.95;
    voz.pitch = 1;
    voz.onend = () => {
        vozLeyendo = false;
        vozPausada = false;
        alFinalizar?.();
        actualizarControlesVoz();
    };
    voz.onerror = () => {
        vozLeyendo = false;
        vozPausada = false;
        actualizarControlesVoz();
    };

    return voz;
};

const leerSiguienteEnCola = () => {
    if (!vozDisponible() || vozLeyendo || vozPausada) return;
    const texto = colaVoz.shift();
    if (!texto) {
        actualizarControlesVoz();
        return;
    }

    vozLeyendo = true;
    window.speechSynthesis.speak(crearLectura(texto, leerSiguienteEnCola));
    actualizarControlesVoz();
};

const encolarLecturaAutomatica = (texto) => {
    if (!vozAutomaticaActiva || !texto || !vozDisponible()) return;

    colaVoz.push(texto);
    leerSiguienteEnCola();
};

const leerEnVozAlta = (texto) => {
    if (!texto || !vozDisponible()) return;

    colaVoz = [];
    vozLeyendo = true;
    vozPausada = false;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(crearLectura(texto));
    actualizarControlesVoz();
};

const actualizarEstadoCamara = (mensaje, tipo = "") => {
    if (!estadoCamara) return;

    estadoCamara.textContent = mensaje;
    estadoCamara.dataset.tipo = tipo;
};

const actualizarEtiquetaCamara = (mensaje) => {
    if (etiquetaCamara) etiquetaCamara.textContent = mensaje;
};

const actualizarEstadoEntrenamiento = (mensaje, tipo = "") => {
    if (!estadoEntrenamiento) return;

    estadoEntrenamiento.textContent = mensaje;
    estadoEntrenamiento.dataset.tipo = tipo;
};

const actualizarContexto = (elemento, mensaje, estado = "") => {
    if (!elemento) return;

    elemento.textContent = mensaje;
    elemento.dataset.estado = estado;
};

const actualizarResultadoEnVivo = (
    coincidencia,
    detalle,
    estado = "esperando",
) => {
    if (coincidenciaEnVivo) coincidenciaEnVivo.textContent = coincidencia;
    if (estabilidadEnVivo) estabilidadEnVivo.textContent = detalle;
    if (resultadoEnVivo) resultadoEnVivo.dataset.estado = estado;
};

const limpiarCapturaPrediccion = () => {
    window.clearTimeout(temporizadorCapturaPrediccion);
    temporizadorCapturaPrediccion = null;
    ultimaPrediccionSena = null;
    panelValidacionSena?.classList.add("oculto");
};

const mostrarValidacionSena = (prediccion, secuencia = []) => {
    const ahora = Date.now();
    if (ultimaPrediccionSena?.expiraEn > ahora) return;

    const capturadaEn = ahora;
    ultimaPrediccionSena = {
        ...prediccion,
        secuencia: Array.isArray(secuencia) ? secuencia.slice() : [],
        capturadaEn,
        expiraEn: ahora + VENTANA_CONFIRMACION_ENTRENAMIENTO_MS,
    };
    const confianza = Number.isFinite(prediccion.confianza)
        ? ` (${Math.round(prediccion.confianza * 100)}%)`
        : "";
    if (textoPrediccionSena)
        textoPrediccionSena.textContent = `${prediccion.texto}${confianza} - captura lista`;
    if (botonConfirmarSena) botonConfirmarSena.disabled = false;
    panelValidacionSena?.classList.remove("oculto");
    actualizarEstadoEntrenamiento(
        `Captura de "${prediccion.texto}" guardada temporalmente. Puede retirar las manos y confirmar durante los proximos 20 segundos.`,
        "ok",
    );

    window.clearTimeout(temporizadorCapturaPrediccion);
    temporizadorCapturaPrediccion = window.setTimeout(() => {
        if (ultimaPrediccionSena?.capturadaEn !== capturadaEn) return;

        ultimaPrediccionSena = null;
        temporizadorCapturaPrediccion = null;
        if (botonConfirmarSena) botonConfirmarSena.disabled = true;
        if (textoPrediccionSena) {
            textoPrediccionSena.textContent = `${prediccion.texto}${confianza} - captura vencida`;
        }
        actualizarEstadoEntrenamiento(
            "La captura vencio despues de 20 segundos. Repita la sena para generar una nueva.",
            "error",
        );
    }, VENTANA_CONFIRMACION_ENTRENAMIENTO_MS);
};

const agregarTextoReconocido = (texto) => {
    const ahora = Date.now();
    const textoLimpio = texto?.trim() || "";
    if (
        !textoLimpio ||
        (textoLimpio === ultimaSenaReconocida && ahora - ultimaSenaReconocidaEn < COOLDOWN_MISMA_SENA_MS)
    ) return;
    if (textoLimpio.toLowerCase().includes("interpretando")) return;

    ultimaSenaReconocida = textoLimpio;
    ultimaSenaReconocidaEn = ahora;
    fragmentosReconocidos.push(textoLimpio);
    renderizarTranscripcion();
    encolarLecturaAutomatica(textoLimpio);
};

const renderizarTranscripcion = () => {
    textoReconocido = fragmentosReconocidos.join(" ").trim();
    if (salida) {
        salida.textContent = textoReconocido || "Esperando senas del paciente...";
    }
    if (contadorTranscripcion) {
        const total = fragmentosReconocidos.length;
        contadorTranscripcion.textContent = `${total} ${total === 1 ? "fragmento confirmado" : "fragmentos confirmados"}`;
    }
    if (botonDeshacerTranscripcion) {
        botonDeshacerTranscripcion.disabled = fragmentosReconocidos.length === 0;
    }
    if (botonLimpiarTranscripcion) {
        botonLimpiarTranscripcion.disabled = fragmentosReconocidos.length === 0;
    }
    actualizarControlesVoz();
};

const registrarFrameManos = (manos) => {
    const ahora = Date.now();
    if (manos?.length) {
        ultimoFrameConMano = ahora;
    }

    const frame = {
        t: ahora,
        manos,
        rostro: rostroActual,
        cuerpo: cuerpoActual,
    };

    historialManos.push(frame);

    historialManos = historialManos.filter((frame) => ahora - frame.t <= 4200);
    if (!reconocimientoPausadoPorEntrenamiento) {
        actualizarSegmentador(frame);
    }

    if (capturandoCorreccion) {
        secuenciaCorreccion.push(frame);
        secuenciaCorreccion = secuenciaCorreccion.slice(-220);

        if (manos?.length) {
            correccionVioManos = true;
            ultimoFrameCorreccionConManos = ahora;
        } else if (
            correccionVioManos &&
            ahora - ultimoFrameCorreccionConManos >= 500
        ) {
            capturandoCorreccion = false;
            actualizarEstadoEntrenamiento(
                "Captura de correccion lista. Puede bajar las manos y pulsar Guardar correccion cuando este preparado.",
                "ok",
            );
        }
    }

    if (grabandoFrase) {
        secuenciaGrabada.push(frame);
        secuenciaGrabada = secuenciaGrabada.slice(-220);
        actualizarCalidadGrabacion();
    }
};

const hayMovimientoReciente = (
    secuencia = historialManos,
    exigirRecencia = true,
) => {
    const ahora = Date.now();
    const framesVisibles = secuencia.filter((frame) =>
        frame?.manos?.length &&
        (!exigirRecencia || !frame.t || ahora - frame.t < 4000),
    );

    return framesVisibles.length >= 5;
};

const describirSegmento = (secuencia) => {
    const frames = Array.isArray(secuencia) ? secuencia : [];
    const visibles = frames.filter((frame) => frame.manos?.length).length;

    return {
        inicio: frames[0]?.t || Date.now(),
        fin: frames.at(-1)?.t || Date.now(),
        duracion: Math.max(
            0,
            (frames.at(-1)?.t || Date.now()) - (frames[0]?.t || Date.now()),
        ),
        frames: frames.length,
        visibles,
    };
};

const intensidadMovimientoEntreFrames = (anterior, actual) => {
    const manosAnteriores = anterior?.manos || [];
    const manosActuales = actual?.manos || [];
    if (!manosAnteriores.length || !manosActuales.length) return 0;

    const puntosClave = [0, 4, 8, 12, 16, 20];
    const cantidad = Math.min(manosAnteriores.length, manosActuales.length);
    let suma = Math.abs(manosAnteriores.length - manosActuales.length) * 0.035;

    for (let indiceMano = 0; indiceMano < cantidad; indiceMano += 1) {
        const manoAnterior = manosAnteriores[indiceMano];
        const manoActual = manosActuales[indiceMano];
        const escala = Math.max(
            0.025,
            Math.hypot(
                (manoActual[0]?.x || 0) - (manoActual[9]?.x || 0),
                (manoActual[0]?.y || 0) - (manoActual[9]?.y || 0),
            ),
        );
        let desplazamiento = 0;

        puntosClave.forEach((punto) => {
            desplazamiento += Math.hypot(
                (manoActual[punto]?.x || 0) - (manoAnterior[punto]?.x || 0),
                (manoActual[punto]?.y || 0) - (manoAnterior[punto]?.y || 0),
                ((manoActual[punto]?.z || 0) - (manoAnterior[punto]?.z || 0)) * 0.55,
            ) / escala;
        });

        suma += desplazamiento / puntosClave.length;
    }

    return suma / Math.max(1, cantidad);
};

const encolarSegmentoReconocimiento = (secuencia) => {
    if (reconocimientoPausadoPorEntrenamiento) return false;

    const segmento = describirSegmento(secuencia);
    if (
        segmento.frames < 8 ||
        segmento.visibles < 6 ||
        segmento.duracion < 280
    ) return false;

    const ultimoEnCola = colaSegmentosReconocimiento.at(-1);
    if (ultimoEnCola && segmento.fin <= ultimoEnCola.segmento.fin + 80) {
        return false;
    }

    colaSegmentosReconocimiento.push({
        secuencia,
        segmento,
        generacion: generacionCamara,
        modoEntrenamiento:
            botonToggleEntrenamiento?.dataset.activo === "true",
    });
    if (colaSegmentosReconocimiento.length > MAX_SEGMENTOS_EN_COLA) {
        colaSegmentosReconocimiento.shift();
    }

    actualizarResultadoEnVivo(
        "Analizando sena...",
        "Comparando el gesto actual con el diccionario aprendido.",
        "provisional",
    );
    procesarColaReconocimiento();
    return true;
};

const finalizarSegmentoEnCurso = (fin) => {
    if (!segmentoEnCurso) return;

    const inicio = Math.max(
        historialManos[0]?.t || segmentoEnCurso.inicio,
        segmentoEnCurso.inicio - 75,
    );
    const secuencia = historialManos.filter(
        (frame) => frame.t >= inicio && frame.t <= fin,
    );

    encolarSegmentoReconocimiento(secuencia);
    segmentoEnCurso = null;
    esperandoCambioParaNuevaSena = true;
};

const actualizarSegmentador = (frame) => {
    const ahora = frame.t || Date.now();
    const hayManos = Boolean(frame.manos?.length);
    const intensidad = intensidadMovimientoEntreFrames(ultimoFrameSegmentador, frame);
    const hayCambio = intensidad >= UMBRAL_MOVIMIENTO_SENA;

    if (!hayManos) {
        inicioAusenciaManos ||= ahora;
        if (
            segmentoEnCurso &&
            ahora - segmentoEnCurso.inicio >= DURACION_MINIMA_SENA_MS &&
            ahora - segmentoEnCurso.ultimoMovimiento >= 80
        ) {
            finalizarSegmentoEnCurso(ahora);
        }
        if (ahora - inicioAusenciaManos >= 180) {
            esperandoCambioParaNuevaSena = false;
        }
        if (ahora - inicioAusenciaManos >= AUSENCIA_REINICIA_REPETICION_MS) {
            permiteRepetirUltimaSena = true;
        }
        ultimoFrameSegmentador = frame;
        return;
    }

    inicioAusenciaManos = 0;
    const primeraManoTrasAusencia = !ultimoFrameSegmentador?.manos?.length;

    if (!segmentoEnCurso) {
        if (
            !esperandoCambioParaNuevaSena ||
            hayCambio ||
            primeraManoTrasAusencia
        ) {
            segmentoEnCurso = {
                inicio: Math.max(historialManos[0]?.t || ahora, ahora - 90),
                ultimoMovimiento: ahora,
            };
        }
        ultimoFrameSegmentador = frame;
        return;
    }

    if (hayCambio) {
        segmentoEnCurso.ultimoMovimiento = ahora;
    }

    const duracion = ahora - segmentoEnCurso.inicio;
    const pausa = ahora - segmentoEnCurso.ultimoMovimiento;
    if (
        duracion >= DURACION_MINIMA_SENA_MS &&
        (pausa >= PAUSA_CIERRE_SENA_MS || duracion >= DURACION_MAXIMA_SENA_MS)
    ) {
        finalizarSegmentoEnCurso(ahora);
    }

    ultimoFrameSegmentador = frame;
};

const actualizarCalidadGrabacion = () => {
    if (!contextoCalidad || !grabandoFrase) return;

    const visibles = secuenciaGrabada.filter((frame) => frame.manos?.length).length;
    const inicio = secuenciaGrabada[0]?.t || Date.now();
    const duracion = Math.max(0, Date.now() - inicio);
    const continuidad = secuenciaGrabada.length
        ? visibles / secuenciaGrabada.length
        : 0;
    const calidad = Math.min(
        100,
        Math.round(
            Math.min(1, visibles / 18) * 45 +
            Math.min(1, duracion / 1200) * 30 +
            continuidad * 25,
        ),
    );

    contextoCalidad.textContent = `Captura: ${calidad}%`;
    contextoCalidad.dataset.estado = calidad >= 70 ? "ok" : "alerta";
};

const dibujarManos = (manos = []) => {
    if (!canvasSenas || !videoSenas) return;

    const ancho = videoSenas.videoWidth || 960;
    const alto = videoSenas.videoHeight || 720;
    if (canvasSenas.width !== ancho || canvasSenas.height !== alto) {
        canvasSenas.width = ancho;
        canvasSenas.height = alto;
    }

    const ctx = canvasSenas.getContext("2d");
    ctx.clearRect(0, 0, ancho, alto);
    if (!mostrarPuntosManos) return;
    ctx.lineWidth = Math.max(2, ancho / 360);
    ctx.strokeStyle = "rgba(0, 255, 202, 0.9)";
    ctx.fillStyle = "#ffffff";

    manos.forEach((mano) => {
        CONEXIONES_MANO.forEach(([a, b]) => {
            if (!mano[a] || !mano[b]) return;
            ctx.beginPath();
            ctx.moveTo(mano[a].x * ancho, mano[a].y * alto);
            ctx.lineTo(mano[b].x * ancho, mano[b].y * alto);
            ctx.stroke();
        });

        mano.forEach((punto, indice) => {
            ctx.beginPath();
            ctx.arc(
                punto.x * ancho,
                punto.y * alto,
                [4, 8, 12, 16, 20].includes(indice) ? 5 : 3,
                0,
                Math.PI * 2,
            );
            ctx.fill();
        });
    });
};

const distanciaPuntosMano = (a, b) => Math.hypot(
    (a?.x || 0) - (b?.x || 0),
    (a?.y || 0) - (b?.y || 0),
    ((a?.z || 0) - (b?.z || 0)) * 0.55,
);

const distanciaPuntosMano2D = (a, b) => Math.hypot(
    (a?.x || 0) - (b?.x || 0),
    (a?.y || 0) - (b?.y || 0),
);

const centroManoEnCamara = (mano) => {
    const indices = [0, 5, 9, 13, 17];
    return indices.reduce(
        (centro, indice) => ({
            x: centro.x + (mano[indice]?.x || 0) / indices.length,
            y: centro.y + (mano[indice]?.y || 0) / indices.length,
        }),
        { x: 0, y: 0 },
    );
};

const confianzaDeMano = (resultado, indice) => {
    const clasificacion = resultado.multiHandedness?.[indice];
    const entrada = Array.isArray(clasificacion)
        ? clasificacion[0]
        : clasificacion;
    const confianza = Number(entrada?.score ?? entrada?.confidence);

    return Number.isFinite(confianza) ? confianza : 1;
};

const contextoCorporalReciente = () => {
    const ahora = Date.now();
    const cuerpo =
        cuerpoActual && ahora - ultimoCuerpoVisibleEn <= CONTEXTO_CORPORAL_VIGENTE_MS
            ? cuerpoActual
            : null;
    const rostro =
        rostroActual && ahora - ultimoRostroVisibleEn <= CONTEXTO_CORPORAL_VIGENTE_MS
            ? rostroActual
            : null;

    return { cuerpo, rostro };
};

const manoDentroDelAreaDeConversacion = (mano) => {
    const { cuerpo, rostro } = contextoCorporalReciente();
    const anchoHombros = Number(cuerpo?.ancho_hombros) || 0;
    const centro = centroManoEnCamara(mano);

    if (!cuerpo || anchoHombros < 0.09) {
        if (!rostro) {
            return (
                centro.x >= -0.08 &&
                centro.x <= 1.08 &&
                centro.y >= -0.12 &&
                centro.y <= 1.12
            );
        }

        return (
            Math.abs(centro.x - rostro.x) <= 0.58 &&
            centro.y >= rostro.y - 0.48 &&
            centro.y <= rostro.y + 0.95
        );
    }

    const distanciaHorizontal = Math.abs(centro.x - cuerpo.hombro_x) / anchoHombros;
    const limiteSuperior = cuerpo.hombro_y - (anchoHombros * 1.6);
    const limiteInferior = cuerpo.hombro_y + (anchoHombros * 1.9);

    return (
        distanciaHorizontal <= 1.65 &&
        centro.y >= limiteSuperior &&
        centro.y <= limiteInferior
    );
};

const manoLejanaCoherenteConElCuerpo = (mano) => {
    const { cuerpo, rostro } = contextoCorporalReciente();
    const centro = centroManoEnCamara(mano);
    const anchoHombros = Number(cuerpo?.ancho_hombros) || 0;

    if (cuerpo && anchoHombros >= 0.07) {
        const distanciaHorizontal =
            Math.abs(centro.x - cuerpo.hombro_x) / anchoHombros;
        const limiteSuperior = cuerpo.hombro_y - (anchoHombros * 2.2);
        const limiteInferior = cuerpo.hombro_y + (anchoHombros * 2.5);

        return (
            distanciaHorizontal <= 1.9 &&
            centro.y >= limiteSuperior &&
            centro.y <= limiteInferior
        );
    }

    if (rostro) {
        return (
            Math.abs(centro.x - rostro.x) <= 0.52 &&
            centro.y >= rostro.y - 0.38 &&
            centro.y <= rostro.y + 0.82
        );
    }

    return false;
};

const diferenciaFormaMano = (anterior, actual) => {
    const escalaAnterior = Math.max(
        0.001,
        distanciaPuntosMano(anterior[0], anterior[9]),
    );
    const escalaActual = Math.max(
        0.001,
        distanciaPuntosMano(actual[0], actual[9]),
    );
    const puntos = [1, 4, 5, 8, 9, 12, 13, 16, 17, 20];

    return puntos.reduce((suma, indice) => {
        const radioAnterior = distanciaPuntosMano(anterior[0], anterior[indice]) / escalaAnterior;
        const radioActual = distanciaPuntosMano(actual[0], actual[indice]) / escalaActual;

        return suma + Math.abs(radioAnterior - radioActual);
    }, 0) / puntos.length;
};

const esManoPlausible = (resultado, indice, mano) => {
    if (!Array.isArray(mano) || mano.length < 21) return false;
    if (!mano.every((punto) => Number.isFinite(punto?.x) && Number.isFinite(punto?.y))) {
        return false;
    }
    const confianza = confianzaDeMano(resultado, indice);
    const escala = distanciaPuntosMano(mano[0], mano[9]);
    const escala2D = distanciaPuntosMano2D(mano[0], mano[9]);
    const anchoPalma = distanciaPuntosMano(mano[5], mano[17]);
    const anchoPalma2D = distanciaPuntosMano2D(mano[5], mano[17]);
    const proporcionPalma = anchoPalma / Math.max(escala, 0.001);
    const proporcionPalma2D = anchoPalma2D / Math.max(escala2D, 0.001);
    const valoresZ = mano.map((punto) => Number(punto.z) || 0);
    const profundidad = Math.max(...valoresZ) - Math.min(...valoresZ);
    const profundidadNormalizada = profundidad / Math.max(escala, 0.001);
    const manoLejana = escala < TAMANO_MINIMO_MANO;
    const confianzaMinima = manoLejana
        ? CONFIANZA_MINIMA_MANO_LEJANA
        : CONFIANZA_MINIMA_MANO;

    if (
        confianza < confianzaMinima ||
        escala < TAMANO_MINIMO_MANO_LEJANA ||
        escala > TAMANO_MAXIMO_MANO ||
        proporcionPalma < 0.18 ||
        proporcionPalma > 2.8
    ) {
        return false;
    }

    const dedos = [[1, 2, 3, 4], [5, 6, 7, 8], [9, 10, 11, 12], [13, 14, 15, 16], [17, 18, 19, 20]];
    const dedosValidos = dedos.filter((dedo) => {
        const longitud = dedo.slice(1).reduce(
            (suma, punto, posicion) => suma + distanciaPuntosMano(mano[dedo[posicion]], mano[punto]),
            0,
        );
        return longitud / Math.max(escala, 0.001) > 0.28;
    }).length;
    const segmentosValidos = CONEXIONES_MANO.filter(([a, b]) => {
        const proporcion = distanciaPuntosMano(mano[a], mano[b]) / Math.max(escala, 0.001);
        return proporcion >= 0.025 && proporcion <= 1.65;
    }).length;
    const amplitudPuntos = distanciaPuntosMano(mano[4], mano[20]) / Math.max(escala, 0.001);
    const palmaReconocible = proporcionPalma >= 0.24;
    const vistaDeCanto =
        proporcionPalma2D < 0.30 &&
        (proporcionPalma >= 0.18 || profundidadNormalizada >= 0.10);
    const estructuraMinima =
        segmentosValidos >= 17 &&
        dedosValidos >= 2 &&
        (palmaReconocible || vistaDeCanto || amplitudPuntos >= 0.16);
    const contextoValido = manoLejana
        ? manoLejanaCoherenteConElCuerpo(mano)
        : manoDentroDelAreaDeConversacion(mano);

    return estructuraMinima && contextoValido;
};

const etiquetaMediaPipe = (resultado, indice) => {
    const clasificacion = resultado.multiHandedness?.[indice];
    const entrada = Array.isArray(clasificacion)
        ? clasificacion[0]
        : clasificacion;
    const nombre = entrada?.label || entrada?.categoryName || entrada?.displayName;

    return nombre ? `Mano-${nombre}` : null;
};

const asignarEtiquetasManos = (resultado, candidatas, ahora) => {
    const etiquetasUsadas = new Set();

    return candidatas.map(({ mano, indice }) => {
        const etiquetaPreferida = etiquetaMediaPipe(resultado, indice);
        const memoriasRecientes = [...memoriaManos.entries()]
            .filter(([etiqueta, valor]) =>
                !etiquetasUsadas.has(etiqueta) &&
                ahora - valor.t <= RETENCION_MANO_OCULTA_MS * 2,
            )
            .map(([etiqueta, valor]) => ({
                etiqueta,
                distancia: distanciaPuntosMano2D(valor.mano[0], mano[0]),
            }))
            .sort((a, b) => a.distancia - b.distancia);
        const memoriaPreferida = etiquetaPreferida
            ? memoriaManos.get(etiquetaPreferida)
            : null;
        const preferidaCercana =
            etiquetaPreferida &&
            !etiquetasUsadas.has(etiquetaPreferida) &&
            (!memoriaPreferida ||
                distanciaPuntosMano2D(memoriaPreferida.mano[0], mano[0]) <= 0.30);
        const etiquetaCercana = memoriasRecientes[0]?.distancia <= 0.22
            ? memoriasRecientes[0].etiqueta
            : null;
        const zona = (mano[0]?.x || 0) < 0.5 ? "Izquierda" : "Derecha";
        let etiqueta = preferidaCercana
            ? etiquetaPreferida
            : etiquetaCercana || etiquetaPreferida || `Mano-${zona}`;

        if (etiquetasUsadas.has(etiqueta)) {
            etiqueta = `Mano-${zona}-${indice}`;
        }
        etiquetasUsadas.add(etiqueta);

        return { etiqueta, mano, indice };
    });
};

const estabilizarManosDetectadas = (resultado) => {
    const ahora = Date.now();
    const landmarks = (resultado.multiHandLandmarks || []).slice(0, 2);
    const candidatas = landmarks
        .map((mano, indice) => ({ mano, indice }))
        .filter(({ mano, indice }) => esManoPlausible(resultado, indice, mano));
    const candidatasConEtiqueta = asignarEtiquetasManos(resultado, candidatas, ahora);

    memoriaManos = new Map(
        [...memoriaManos].filter(([, valor]) => ahora - valor.t <= RETENCION_MANO_OCULTA_MS),
    );

    if (candidatasConEtiqueta.length === 0) {
        const seguimiento = [...memoriaManos.values()]
            .filter((valor) => valor.confirmaciones >= FRAMES_CONFIRMACION_MANO)
            .map((valor) => valor.mano)
            .slice(0, 2)
            .sort((a, b) => (a[0]?.x || 0) - (b[0]?.x || 0));

        return { reales: [], seguimiento, retenidas: seguimiento.length };
    }

    candidatasConEtiqueta.forEach(({ etiqueta, mano }) => {
        const anterior = memoriaManos.get(etiqueta);
        const esContinua = anterior &&
            ahora - anterior.t <= RETENCION_MANO_OCULTA_MS &&
            (
                diferenciaFormaMano(anterior.mano, mano) <= DIFERENCIA_MAXIMA_FORMA_MANO ||
                distanciaPuntosMano2D(anterior.mano[0], mano[0]) <= 0.18
            );
        const confirmaciones = esContinua
            ? Math.min(FRAMES_CONFIRMACION_MANO, anterior.confirmaciones + 1)
            : 1;
        memoriaManos.set(etiqueta, { mano, t: ahora, confirmaciones });
    });

    const realesConEtiqueta = candidatasConEtiqueta.filter(({ etiqueta }) =>
        (memoriaManos.get(etiqueta)?.confirmaciones || 0) >= FRAMES_CONFIRMACION_MANO,
    );

    const etiquetasVisibles = new Set(
        realesConEtiqueta.map(({ etiqueta }) => etiqueta),
    );
    const seguimiento = realesConEtiqueta.map(({ mano }) => mano);
    let retenidas = 0;

    memoriaManos.forEach((valor, etiqueta) => {
        if (
            seguimiento.length < 2 &&
            !etiquetasVisibles.has(etiqueta) &&
            ahora - valor.t <= RETENCION_MANO_OCULTA_MS &&
            valor.confirmaciones >= FRAMES_CONFIRMACION_MANO
        ) {
            seguimiento.push(valor.mano);
            retenidas += 1;
        }
    });

    seguimiento.sort((a, b) => (a[0]?.x || 0) - (b[0]?.x || 0));
    const reales = realesConEtiqueta
        .map(({ mano }) => mano)
        .sort((a, b) => (a[0]?.x || 0) - (b[0]?.x || 0));

    return { reales, seguimiento, retenidas };
};

const registrarMovimientoCabeza = (nariz) => {
    const ahora = Date.now();
    cabezaActual = {
        x: nariz.x,
        y: nariz.y,
        z: nariz.z ?? 0,
    };

    historialCabeza.push({
        t: ahora,
        x: nariz.x,
        y: nariz.y,
        z: nariz.z ?? 0,
    });
    historialCabeza = historialCabeza.filter(
        (punto) => ahora - punto.t <= 1200,
    );

    if (ahora - ultimoGestoCabeza < 1800 || historialCabeza.length < 6) {
        return null;
    }

    const xs = historialCabeza.map((punto) => punto.x);
    const ys = historialCabeza.map((punto) => punto.y);
    const rangoX = Math.max(...xs) - Math.min(...xs);
    const rangoY = Math.max(...ys) - Math.min(...ys);

    if (
        (rangoX > 0.055 && rangoX > rangoY * 1.35) ||
        (rangoY > 0.045 && rangoY > rangoX * 1.15)
    ) {
        ultimoGestoCabeza = ahora;
    }

    return null;
};

// ==================== COMUNICACION CON PANTALLA EXTERNA DEL PACIENTE ====================
const publicarMensajeDoctor = (mensaje) => {
    if (!mensaje) return;

    const payload = {
        mensaje,
        fecha: new Date().toISOString(),
    };

    localStorage.setItem("medisignMensajeDoctor", JSON.stringify(payload));
    canalPaciente?.postMessage(payload);

    if (ultimoMensaje) ultimoMensaje.textContent = mensaje;
};

const abrirPantallaPaciente = () => {
    if (ventanaPaciente && !ventanaPaciente.closed) {
        ventanaPaciente.focus();
        return ventanaPaciente;
    }

    ventanaPaciente = window.open(
        "/pantalla-paciente",
        "medisignPantallaPaciente",
        "width=980,height=720,resizable=yes,scrollbars=yes",
    );

    return ventanaPaciente;
};

const enviarAPantallaPaciente = () => {
    const mensaje = mensajeDoctor?.value.trim();
    if (!mensaje) return;

    abrirPantallaPaciente();
    publicarMensajeDoctor(mensaje);
};

// ==================== RECONOCIMIENTO REAL DE SENAS CON MEDIAPIPE HANDS ====================
const procesarPrediccionEstable = (data, secuenciaEnviada = []) => {
    const ahora = Date.now();

    if (!data.codigo || !data.texto) {
        const candidato = data.candidato;
        if (candidato?.texto) {
            const porcentajeCandidato = Math.round(
                (Number(candidato.confianza) || 0) * 100,
            );
            actualizarResultadoEnVivo(
                `${candidato.texto}: ${porcentajeCandidato}%`,
                "Coincidencia posible. Haga la sena completa para confirmarla.",
                "provisional",
            );
            actualizarEstadoCamara(
                `Posible sena: ${candidato.texto} (${porcentajeCandidato}%).`,
            );
            actualizarEtiquetaCamara("Posible");
            const modoEntrenamiento =
                botonToggleEntrenamiento?.dataset.activo === "true";
            if (modoEntrenamiento && candidato.codigo) {
                mostrarValidacionSena(
                    {
                        codigo: candidato.codigo,
                        texto: candidato.texto,
                        confianza: Number(candidato.confianza) || 0,
                        agregadaAlTexto: false,
                    },
                    secuenciaEnviada,
                );
            }
            return;
        }

        const sinEntrenamiento = data.estado === "sin_entrenamiento";
        actualizarResultadoEnVivo(
            sinEntrenamiento ? "Sin frases entrenadas" : "Sin coincidencia segura",
            data.texto || "Mantenga la sena visible un momento.",
            sinEntrenamiento ? "esperando" : "sin-coincidencia",
        );
        actualizarEstadoCamara(
            data.texto || "Analizando la sena completa...",
        );
        return;
    }

    const confianza = Number(data.confianza) || 0;
    const porcentaje = Math.round(confianza * 100);

    actualizarResultadoEnVivo(
        `${data.texto}: ${porcentaje}%`,
        "Coincidencia validada. Agregando la sena al texto reconocido.",
        "provisional",
    );

    if (
        data.codigo === ultimoCodigoConfirmado &&
        !permiteRepetirUltimaSena
    ) {
        actualizarResultadoEnVivo(
            `${data.texto}: ${porcentaje}%`,
            "Misma sena mantenida. No se vuelve a agregar al texto.",
            "provisional",
        );
        return;
    }

    const ultimaEmision = ultimaEmisionPorCodigo.get(data.codigo) || 0;
    if (ahora - ultimaEmision < COOLDOWN_EMISION_CODIGO_MS) {
        actualizarResultadoEnVivo(
            `${data.texto}: ${porcentaje}%`,
            "Sena ya identificada. Cambie de gesto para continuar.",
            "provisional",
        );
        return;
    }

    ultimaEmisionPorCodigo.set(data.codigo, ahora);
    ultimoCodigoConfirmado = data.codigo;
    permiteRepetirUltimaSena = false;
    agregarTextoReconocido(data.texto);
    actualizarResultadoEnVivo(
        `${data.texto}: ${porcentaje}%`,
        "Sena confirmada y agregada al texto reconocido.",
        "confirmado",
    );
    actualizarEstadoCamara(
        `Sena confirmada: ${data.texto} (${porcentaje}%)`,
        "ok",
    );
    actualizarEtiquetaCamara("Confirmada");
    mostrarValidacionSena(
        {
            codigo: data.codigo,
            texto: data.texto,
            confianza,
            agregadaAlTexto: true,
        },
        secuenciaEnviada,
    );
};

const entregarResultadosReconocimientoEnOrden = () => {
    while (resultadosReconocimientoPendientes.has(siguienteResultadoReconocimiento)) {
        const resultado = resultadosReconocimientoPendientes.get(
            siguienteResultadoReconocimiento,
        );
        resultadosReconocimientoPendientes.delete(siguienteResultadoReconocimiento);
        siguienteResultadoReconocimiento += 1;

        if (
            resultado?.data?.success &&
            resultado.generacion === generacionCamara &&
            camaraActiva &&
            !reconocimientoPausadoPorEntrenamiento
        ) {
            procesarPrediccionEstable(resultado.data, resultado.secuencia);
        } else if (
            resultado?.error &&
            resultado.generacion === generacionCamara &&
            camaraActiva &&
            !reconocimientoPausadoPorEntrenamiento
        ) {
            console.error("Error al reconocer senas:", resultado.error);
            actualizarEstadoCamara(
                resultado.error.message || "No se pudo reconocer la sena. Intente de nuevo.",
                "error",
            );
            actualizarResultadoEnVivo(
                "Analisis interrumpido",
                "El sistema se recupero. Repita la sena frente a la camara.",
                "sin-coincidencia",
            );
        }
    }
};

const procesarTrabajoReconocimiento = async (trabajo, orden) => {
    let resultado;
    const controladorTiempo = new AbortController();
    const temporizadorTiempo = window.setTimeout(
        () => controladorTiempo.abort(),
        TIMEOUT_RECONOCIMIENTO_MS,
    );
    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const respuesta = await fetch("/api/reconocer-sena-conversacion", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": token,
            },
            signal: controladorTiempo.signal,
            body: JSON.stringify({
                puntos: trabajo.secuencia.at(-1)?.manos?.[0] || null,
                manos: trabajo.secuencia.at(-1)?.manos || [],
                secuencia: trabajo.secuencia,
                modo_entrenamiento: trabajo.modoEntrenamiento,
            }),
        });

        if (!respuesta.ok) throw new Error("No se pudo procesar la sena");

        const data = await respuesta.json();
        resultado = {
            data,
            secuencia: trabajo.secuencia,
            generacion: trabajo.generacion,
        };
    } catch (error) {
        const errorNormalizado = error?.name === "AbortError"
            ? new Error("El analisis supero el tiempo maximo y fue cancelado.")
            : error;
        resultado = {
            error: errorNormalizado,
            generacion: trabajo.generacion,
        };
    } finally {
        window.clearTimeout(temporizadorTiempo);
        resultadosReconocimientoPendientes.set(orden, resultado);
        reconocimientosActivos = Math.max(0, reconocimientosActivos - 1);
        entregarResultadosReconocimientoEnOrden();
        procesarColaReconocimiento();
    }
};

const procesarColaReconocimiento = () => {
    while (
        reconocimientosActivos < MAX_RECONOCIMIENTOS_CONCURRENTES &&
        colaSegmentosReconocimiento.length
    ) {
        const trabajo = colaSegmentosReconocimiento.shift();
        const orden = siguienteSolicitudReconocimiento;
        siguienteSolicitudReconocimiento += 1;
        reconocimientosActivos += 1;
        procesarTrabajoReconocimiento(trabajo, orden);
    }
};

const guardarMuestraEntrenamiento = async (
    codigo,
    textoPersonalizado = "",
    secuenciaPersonalizada = null,
) => {
    const secuencia = secuenciaPersonalizada?.length
        ? secuenciaPersonalizada
        : historialManos;

    if (!secuencia?.length) {
        actualizarEstadoEntrenamiento(
            "Primero realice la sena frente a la camara.",
            "error",
        );
        return false;
    }

    if (!hayMovimientoReciente(secuencia, !secuenciaPersonalizada?.length)) {
        actualizarEstadoEntrenamiento(
            "No hay suficientes fotogramas de la sena para guardar. Repita el gesto completo.",
            "error",
        );
        return false;
    }

    const ultimoFrameVisible = [...secuencia]
        .reverse()
        .find((frame) => frame?.manos?.length);
    const manosCapturadas = ultimoFrameVisible?.manos || ultimasManos;
    const puntosCapturados = manosCapturadas?.[0] || ultimosPuntosMano || null;

    try {
        actualizarEstadoEntrenamiento("Guardando ejemplo de entrenamiento...");
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const respuesta = await fetch("/api/senas-conversacion/muestras", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": token,
            },
            body: JSON.stringify({
                codigo,
                texto: textoPersonalizado,
                puntos: puntosCapturados,
                manos: manosCapturadas,
                secuencia,
            }),
        });

        const data = await respuesta.json();
        if (!respuesta.ok || !data.success) {
            throw new Error(data.message || "No se pudo guardar la muestra");
        }

        actualizarEstadoEntrenamiento(
            `Guardado: ${data.texto}. Calidad ${Math.round((data.calidad || 0) * 100)}%. Ejemplos: ${data.total}. ${data.recomendadas_restantes ? `Faltan ${data.recomendadas_restantes} recomendados.` : "Entrenamiento base completo."}`,
            "ok",
        );
        if (contextoCalidad) {
            contextoCalidad.textContent = `Ultima captura: ${Math.round((data.calidad || 0) * 100)}%`;
            contextoCalidad.dataset.estado = "ok";
        }
        cargarFrasesAprendidas();
        return true;
    } catch (error) {
        console.error("Error al guardar muestra:", error);
        actualizarEstadoEntrenamiento(
            error.message || "No se pudo guardar el ejemplo. Intente otra vez.",
            "error",
        );
        return false;
    }
};

const pausarReconocimientoParaEntrenamiento = () => {
    if (reconocimientoPausadoPorEntrenamiento) return;

    reconocimientoPausadoPorEntrenamiento = true;
    generacionCamara += 1;
    colaSegmentosReconocimiento = [];
    segmentoEnCurso = null;
    ultimoFrameSegmentador = null;
    inicioAusenciaManos = 0;
    esperandoCambioParaNuevaSena = false;
    actualizarResultadoEnVivo(
        "Entrenamiento en curso",
        "La traduccion esta pausada mientras se captura esta muestra.",
        "provisional",
    );
};

const reanudarReconocimientoDespuesDeEntrenar = () => {
    if (!reconocimientoPausadoPorEntrenamiento) return;

    reconocimientoPausadoPorEntrenamiento = false;
    segmentoEnCurso = null;
    ultimoFrameSegmentador = null;
    inicioAusenciaManos = 0;
    esperandoCambioParaNuevaSena = false;
    actualizarResultadoEnVivo(
        "Esperando una sena...",
        "La traduccion en tiempo real esta activa nuevamente.",
        "esperando",
    );
};

const comenzarCapturaFrase = () => {
    pausarReconocimientoParaEntrenamiento();
    grabandoFrase = true;
    secuenciaGrabada = [];
    panelEntrenamientoLibre?.setAttribute("data-grabando", "true");
    if (botonGuardarFraseEntrenada) botonGuardarFraseEntrenada.disabled = false;
    if (botonIniciarGrabacion) {
        botonIniciarGrabacion.textContent = "Grabando";
        botonIniciarGrabacion.disabled = true;
    }
    actualizarEstadoEntrenamiento(
        "Grabando. Haga la sena completa con una o dos manos y luego finalice.",
        "ok",
    );
};

const iniciarGrabacionFrase = () => {
    const texto = fraseEntrenamiento?.value.trim();
    if (!texto) {
        actualizarEstadoEntrenamiento(
            "Primero escriba la frase que significa el gesto.",
            "error",
        );
        fraseEntrenamiento?.focus();
        return;
    }

    if (grabandoFrase || cuentaRegresivaGrabacion) return;

    let restante = 3;
    if (botonIniciarGrabacion) botonIniciarGrabacion.disabled = true;
    actualizarEstadoEntrenamiento(
        `Preparese, mire la camara y coloque ambas manos visibles. Comienza en ${restante}.`,
    );
    if (botonIniciarGrabacion) botonIniciarGrabacion.textContent = String(restante);

    cuentaRegresivaGrabacion = window.setInterval(() => {
        restante -= 1;
        if (restante > 0) {
            actualizarEstadoEntrenamiento(
                `Preparese, mire la camara y coloque ambas manos visibles. Comienza en ${restante}.`,
            );
            if (botonIniciarGrabacion) botonIniciarGrabacion.textContent = String(restante);
            return;
        }

        window.clearInterval(cuentaRegresivaGrabacion);
        cuentaRegresivaGrabacion = null;
        comenzarCapturaFrase();
    }, 1000);
};

const finalizarGrabacionFrase = async () => {
    const texto = fraseEntrenamiento?.value.trim();
    if (!texto) {
        actualizarEstadoEntrenamiento(
            "Escriba la frase antes de guardar.",
            "error",
        );
        return;
    }

    if (!secuenciaGrabada.length) {
        actualizarEstadoEntrenamiento(
            "No se detectaron manos durante la grabacion.",
            "error",
        );
        return;
    }

    grabandoFrase = false;
    panelEntrenamientoLibre?.setAttribute("data-grabando", "false");
    if (botonGuardarFraseEntrenada) botonGuardarFraseEntrenada.disabled = true;
    if (botonIniciarGrabacion)
        botonIniciarGrabacion.textContent = "Iniciar grabacion";
    if (botonIniciarGrabacion) botonIniciarGrabacion.disabled = false;
    const guardado = await guardarMuestraEntrenamiento(
        null,
        texto,
        secuenciaGrabada,
    );
    if (guardado) reanudarReconocimientoDespuesDeEntrenar();
};

const iniciarCapturaCorreccion = (texto) => {
    limpiarCapturaPrediccion();
    pausarReconocimientoParaEntrenamiento();
    capturandoCorreccion = true;
    secuenciaCorreccion = [];
    correccionVioManos = false;
    ultimoFrameCorreccionConManos = 0;
    actualizarEstadoEntrenamiento(
        `Grabando una nueva muestra de "${texto}". Haga la sena completa y luego retire las manos; la captura se detendra sola.`,
        "ok",
    );
};

const renderizarFrasesAprendidas = (frases) => {
    if (contenedorFrasesAprendidas) {
        contenedorFrasesAprendidas.innerHTML = "";

        if (!frases.length) {
            const vacio = document.createElement("p");
            vacio.className = "texto-sin-frases";
            vacio.textContent = "Aun no hay frases guardadas.";
            contenedorFrasesAprendidas.appendChild(vacio);
        }

        frases.forEach((frase) => {
            const grupo = document.createElement("div");
            grupo.className = "frase-aprendida";
            const boton = document.createElement("button");
            boton.type = "button";
            boton.textContent = `${frase.texto} (${frase.total})`;
            boton.addEventListener("click", () => {
                if (fraseCorreccion) fraseCorreccion.value = frase.texto;
                panelCorreccion?.classList.remove("oculto");
                iniciarCapturaCorreccion(frase.texto);
            });
            const eliminar = document.createElement("button");
            eliminar.type = "button";
            eliminar.className = "eliminar-frase";
            eliminar.textContent = "X";
            eliminar.title = `Eliminar ${frase.texto} y todas sus muestras`;
            eliminar.setAttribute("aria-label", eliminar.title);
            eliminar.addEventListener("click", () => eliminarFraseAprendida(frase));

            grupo.append(boton, eliminar);
            contenedorFrasesAprendidas.appendChild(grupo);
        });
    }

    if (opcionesFrases) {
        opcionesFrases.innerHTML = "";
        frases.forEach((frase) => {
            const option = document.createElement("option");
            option.value = frase.texto;
            opcionesFrases.appendChild(option);
        });
    }
};

const eliminarFraseAprendida = async (frase) => {
    if (!window.confirm(`Se eliminaran todas las muestras de "${frase.texto}". Continuar?`)) {
        return;
    }

    try {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";
        const respuesta = await fetch(
            `/api/senas-conversacion/frases/${encodeURIComponent(frase.codigo)}`,
            {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": token,
                },
            },
        );
        const data = await respuesta.json();
        if (!respuesta.ok || !data.success) {
            throw new Error(data.message || "No se pudo eliminar la frase");
        }

        actualizarEstadoEntrenamiento(data.message, "ok");
        cargarFrasesAprendidas();
    } catch (error) {
        actualizarEstadoEntrenamiento(error.message, "error");
    }
};

const cargarFrasesAprendidas = async (busqueda = "") => {
    try {
        const params = new URLSearchParams();
        if (busqueda.trim()) params.set("q", busqueda.trim());

        const respuesta = await fetch(
            `/api/senas-conversacion/frases?${params.toString()}`,
            {
                headers: {
                    Accept: "application/json",
                },
            },
        );
        const data = await respuesta.json();

        if (!respuesta.ok || !data.success) return;
        renderizarFrasesAprendidas(data.frases || []);
    } catch (error) {
        console.error("Error al cargar frases aprendidas:", error);
    }
};

const guardarCorreccionLibre = async () => {
    const texto = fraseCorreccion?.value.trim();
    if (!texto) {
        actualizarEstadoEntrenamiento(
            "Escriba o seleccione la frase correcta.",
            "error",
        );
        fraseCorreccion?.focus();
        return;
    }

    const secuenciaCapturada = ultimaPrediccionSena?.secuencia?.length
        ? ultimaPrediccionSena.secuencia
        : secuenciaCorreccion.length
            ? secuenciaCorreccion
            : historialManos;
    capturandoCorreccion = false;
    const guardado = await guardarMuestraEntrenamiento(
        null,
        texto,
        secuenciaCapturada,
    );
    if (!guardado) return;

    limpiarCapturaPrediccion();
    secuenciaCorreccion = [];
    correccionVioManos = false;
    ultimoFrameCorreccionConManos = 0;
    panelCorreccion?.classList.add("oculto");
    reanudarReconocimientoDespuesDeEntrenar();
    window.setTimeout(() => cargarFrasesAprendidas(), 450);
};

const alternarModoEntrenamiento = () => {
    const activo = botonToggleEntrenamiento?.dataset.activo === "true";
    const nuevoEstado = !activo;

    botonToggleEntrenamiento.dataset.activo = String(nuevoEstado);
    botonToggleEntrenamiento.textContent = nuevoEstado
        ? "Desactivar modo entrenamiento"
        : "Activar modo entrenamiento";
    panelEntrenamiento?.classList.toggle("oculto", !nuevoEstado);

    if (nuevoEstado) {
        cargarFrasesAprendidas();
    } else {
        grabandoFrase = false;
        capturandoCorreccion = false;
        secuenciaGrabada = [];
        secuenciaCorreccion = [];
        panelCorreccion?.classList.add("oculto");
        limpiarCapturaPrediccion();
        reanudarReconocimientoDespuesDeEntrenar();
    }
};

const obtenerPistaCamara = () =>
    videoSenas?.srcObject?.getVideoTracks?.()[0] || null;

const actualizarControlesCamara = () => {
    if (botonToggleCamara) {
        botonToggleCamara.disabled =
            !controladorCamara || cambioCamaraEnCurso;
        botonToggleCamara.dataset.activo = String(camaraActiva);
        botonToggleCamara.textContent = camaraActiva
            ? "Desactivar camara"
            : "Activar camara";
    }
    if (botonEnfocarCamara) {
        botonEnfocarCamara.disabled = !camaraActiva || cambioCamaraEnCurso;
    }
};

const limpiarEstadoCamaraDesactivada = () => {
    colaSegmentosReconocimiento = [];
    segmentoEnCurso = null;
    ultimoFrameSegmentador = null;
    inicioAusenciaManos = 0;
    esperandoCambioParaNuevaSena = false;
    ultimasManos = [];
    ultimosPuntosMano = null;
    memoriaManos = new Map();
    dibujarManos([]);
    guiaCamara?.classList.remove("oculto");
    actualizarContexto(contextoMano, "Mano: camara apagada", "alerta");
    actualizarContexto(contextoRostro, "Rostro: camara apagada", "alerta");
    actualizarContexto(contextoTorso, "Torso: camara apagada", "alerta");
    actualizarResultadoEnVivo(
        "Camara desactivada",
        "Active la camara para continuar con el reconocimiento.",
        "esperando",
    );
};

const desactivarCamaraSenas = async () => {
    if (!controladorCamara || !camaraActiva || cambioCamaraEnCurso) return;

    cambioCamaraEnCurso = true;
    camaraActiva = false;
    generacionCamara += 1;
    actualizarControlesCamara();

    try {
        await controladorCamara.stop();
    } catch (error) {
        console.warn("No se pudo detener la camara limpiamente:", error);
        videoSenas?.srcObject?.getTracks?.().forEach((track) => track.stop());
    } finally {
        limpiarEstadoCamaraDesactivada();
        actualizarEstadoCamara("Camara desactivada por el usuario.");
        actualizarEtiquetaCamara("Apagada");
        cambioCamaraEnCurso = false;
        actualizarControlesCamara();
    }
};

const activarCamaraSenas = async () => {
    if (!controladorCamara || camaraActiva || cambioCamaraEnCurso) return;

    cambioCamaraEnCurso = true;
    actualizarControlesCamara();
    actualizarEstadoCamara("Activando camara...");
    actualizarEtiquetaCamara("Preparando");

    try {
        await controladorCamara.start();
        camaraActiva = true;
        actualizarEstadoCamara(
            "Camara activa. Muestre una sena de conversacion frente a la pantalla.",
            "ok",
        );
        actualizarEtiquetaCamara("En vivo");
    } catch (error) {
        console.error("Error al iniciar camara de senas:", error);
        camaraActiva = false;
        actualizarEstadoCamara(
            "No se pudo acceder a la camara. Revise permisos del navegador.",
            "error",
        );
        actualizarEtiquetaCamara("Sin camara");
    } finally {
        cambioCamaraEnCurso = false;
        actualizarControlesCamara();
    }
};

const enfocarCamaraSenas = async () => {
    const pista = obtenerPistaCamara();
    if (!camaraActiva || !pista) {
        actualizarEstadoCamara("Active la camara antes de intentar enfocar.", "error");
        return;
    }

    try {
        const capacidades = pista.getCapabilities?.() || {};
        const modos = Array.isArray(capacidades.focusMode)
            ? capacidades.focusMode
            : [];
        const modo = modos.includes("single-shot")
            ? "single-shot"
            : modos.includes("continuous")
                ? "continuous"
                : null;

        if (!modo) {
            actualizarEstadoCamara(
                "Esta camara tiene foco fijo o no permite enfocarla desde el navegador.",
                "error",
            );
            return;
        }

        await pista.applyConstraints({ advanced: [{ focusMode: modo }] });
        actualizarEstadoCamara(
            modo === "single-shot"
                ? "Enfoque solicitado. Mantenga las manos quietas un momento."
                : "Autoenfoque continuo activado.",
            "ok",
        );
    } catch (error) {
        console.warn("La camara rechazo el enfoque solicitado:", error);
        actualizarEstadoCamara(
            "El dispositivo no permitio cambiar el enfoque.",
            "error",
        );
    }
};

const iniciarReconocimientoReal = async () => {
    if (!videoSenas || !window.Hands || !window.Camera) {
        actualizarEstadoCamara(
            "MediaPipe no esta disponible en esta pantalla.",
            "error",
        );
        actualizarEtiquetaCamara("Sin detector");
        actualizarControlesCamara();
        return;
    }

    if (salida) salida.textContent = "Esperando senas del paciente...";
    colaSegmentosReconocimiento = [];
    reconocimientosActivos = 0;
    siguienteSolicitudReconocimiento = 1;
    siguienteResultadoReconocimiento = 1;
    resultadosReconocimientoPendientes = new Map();
    segmentoEnCurso = null;
    ultimoFrameSegmentador = null;
    inicioAusenciaManos = 0;
    esperandoCambioParaNuevaSena = false;
    ultimoCodigoConfirmado = "";
    permiteRepetirUltimaSena = true;
    actualizarEstadoCamara("Activando camara...");
    actualizarEtiquetaCamara("Camara");

    const hands = new Hands({
        locateFile: (file) => `/vendor/mediapipe/hands/${file}`,
    });

    const pose = window.Pose
        ? new Pose({
              locateFile: (file) => `/vendor/mediapipe/pose/${file}`,
          })
        : null;

    const faceMesh = window.FaceMesh
        ? new FaceMesh({
              locateFile: (file) => `/vendor/mediapipe/face_mesh/${file}`,
          })
        : null;

    hands.setOptions({
        maxNumHands: 2,
        modelComplexity: 1,
        minDetectionConfidence: 0.60,
        minTrackingConfidence: 0.50,
    });

    pose?.setOptions({
        modelComplexity: 1,
        smoothLandmarks: true,
        minDetectionConfidence: 0.36,
        minTrackingConfidence: 0.30,
    });

    faceMesh?.setOptions({
        maxNumFaces: 1,
        refineLandmarks: false,
        minDetectionConfidence: 0.36,
        minTrackingConfidence: 0.30,
    });

    hands.onResults((results) => {
        const manosEstables = estabilizarManosDetectadas(results);

        if (manosEstables.seguimiento.length > 0) {
            ultimasManos = manosEstables.seguimiento;
            registrarFrameManos(ultimasManos);
            dibujarManos(manosEstables.seguimiento);
            guiaCamara?.classList.add("oculto");
            ultimosPuntosMano = ultimasManos[0];
            const totalManos = manosEstables.seguimiento.length;
            const hayManoLejana = manosEstables.seguimiento.some(
                (mano) => distanciaPuntosMano(mano[0], mano[9]) < TAMANO_MINIMO_MANO,
            );
            const soloSeguimientoTemporal = manosEstables.reales.length === 0;
            const mensajeManos = soloSeguimientoTemporal
                ? `${totalManos === 2 ? "Manos" : "Mano"}: seguimiento temporal`
                : manosEstables.retenidas > 0
                    ? "Manos: 2 seguidas (1 oculta brevemente)"
                : totalManos === 2
                    ? `Manos: 2 detectadas${hayManoLejana ? " a distancia" : ""}`
                    : `Mano: 1 detectada${hayManoLejana ? " a distancia" : ""}`;
            actualizarContexto(
                contextoMano,
                mensajeManos,
                "ok",
            );
            actualizarEstadoCamara(
                soloSeguimientoTemporal
                    ? "Manteniendo la mano durante una oclusion breve..."
                    : "Mano detectada. Interpretando sena...",
            );
            return;
        }

        ultimasManos = [];
        ultimosPuntosMano = null;
        registrarFrameManos([]);
        dibujarManos([]);
        guiaCamara?.classList.remove("oculto");

        actualizarContexto(contextoMano, "Mano: esperando", "alerta");
        actualizarEstadoCamara("Esperando mano del paciente...");
    });

    pose?.onResults((results) => {
        const landmarks = results.poseLandmarks;
        if (!landmarks || landmarks.length < 13) {
            if (Date.now() - ultimoCuerpoVisibleEn > CONTEXTO_CORPORAL_VIGENTE_MS) {
                cuerpoActual = null;
            }
            actualizarContexto(contextoTorso, "Torso: no visible", "alerta");
            return;
        }

        const hombroIzq = landmarks[11];
        const hombroDer = landmarks[12];
        const caderaIzq = landmarks[23];
        const caderaDer = landmarks[24];
        const hombrosVisibles =
            (hombroIzq.visibility ?? 0) > 0.32 &&
            (hombroDer.visibility ?? 0) > 0.32;
        const inclinacion = Math.abs(hombroIzq.y - hombroDer.y);
        if (hombrosVisibles) {
            const hombroX = (hombroIzq.x + hombroDer.x) / 2;
            const hombroY = (hombroIzq.y + hombroDer.y) / 2;
            const hombroZ = ((hombroIzq.z || 0) + (hombroDer.z || 0)) / 2;
            const caderasVisibles =
                (caderaIzq?.visibility ?? 0) > 0.25 &&
                (caderaDer?.visibility ?? 0) > 0.25;
            const caderaX = caderasVisibles
                ? (caderaIzq.x + caderaDer.x) / 2
                : hombroX;
            const caderaY = caderasVisibles
                ? (caderaIzq.y + caderaDer.y) / 2
                : hombroY + Math.abs(hombroIzq.x - hombroDer.x) * 1.45;
            const caderaZ = caderasVisibles
                ? ((caderaIzq.z || 0) + (caderaDer.z || 0)) / 2
                : hombroZ;
            cuerpoActual = {
                hombro_x: hombroX,
                hombro_y: hombroY,
                ancho_hombros: Math.hypot(
                    hombroIzq.x - hombroDer.x,
                    hombroIzq.y - hombroDer.y,
                ),
                inclinacion: hombroIzq.y - hombroDer.y,
                hombro_izq_x: hombroIzq.x,
                hombro_izq_y: hombroIzq.y,
                hombro_izq_z: hombroIzq.z || 0,
                hombro_der_x: hombroDer.x,
                hombro_der_y: hombroDer.y,
                hombro_der_z: hombroDer.z || 0,
                pecho_x: hombroX + ((caderaX - hombroX) * 0.18),
                pecho_y: hombroY + ((caderaY - hombroY) * 0.36),
                pecho_z: hombroZ + ((caderaZ - hombroZ) * 0.22),
                codo_izq_x: landmarks[13]?.x || 0,
                codo_izq_y: landmarks[13]?.y || 0,
                codo_der_x: landmarks[14]?.x || 0,
                codo_der_y: landmarks[14]?.y || 0,
            };
            ultimoCuerpoVisibleEn = Date.now();
        } else if (Date.now() - ultimoCuerpoVisibleEn > CONTEXTO_CORPORAL_VIGENTE_MS) {
            cuerpoActual = null;
        }

        if (hombrosVisibles && inclinacion < 0.12) {
            actualizarContexto(contextoTorso, "Torso: centrado", "ok");
            return;
        }

        actualizarContexto(contextoTorso, "Torso: acomode postura", "alerta");
    });

    faceMesh?.onResults((results) => {
        const rostro = results.multiFaceLandmarks?.[0];
        if (!rostro) {
            if (Date.now() - ultimoRostroVisibleEn > CONTEXTO_CORPORAL_VIGENTE_MS) {
                rostroActual = null;
                cabezaActual = null;
            }
            actualizarContexto(contextoRostro, "Rostro: no visible", "alerta");
            return;
        }

        const nariz = rostro[1];
        const labioSuperior = rostro[13];
        const labioInferior = rostro[14];
        const bocaAbierta =
            labioSuperior && labioInferior
                ? Math.abs(labioInferior.y - labioSuperior.y) > 0.018
                : false;
        const centrado = nariz && nariz.x > 0.32 && nariz.x < 0.68;
        if (nariz) {
            const ojoIzq = rostro[33];
            const ojoDer = rostro[263];
            const frente = rostro[10];
            const barbilla = rostro[152];
            const anchoOjos = Math.max(
                0.001,
                Math.hypot(ojoIzq.x - ojoDer.x, ojoIzq.y - ojoDer.y),
            );
            const altoRostro = Math.max(0.001, Math.abs(barbilla.y - frente.y));
            const centroOjosX = (ojoIzq.x + ojoDer.x) / 2;
            const centroOjosY = (ojoIzq.y + ojoDer.y) / 2;
            const cejaIzq = rostro[105];
            const cejaDer = rostro[334];
            const cacheteIzq = rostro[234];
            const cacheteDer = rostro[454];
            const bocaX = (labioSuperior.x + labioInferior.x) / 2;
            const bocaY = (labioSuperior.y + labioInferior.y) / 2;
            const bocaZ = ((labioSuperior.z || 0) + (labioInferior.z || 0)) / 2;

            rostroActual = {
                x: nariz.x,
                y: nariz.y,
                z: nariz.z || 0,
                yaw: (nariz.x - centroOjosX) / anchoOjos,
                pitch: (nariz.y - centroOjosY) / altoRostro,
                boca: Math.abs(labioInferior.y - labioSuperior.y) / altoRostro,
                cejas: cejaIzq && cejaDer
                    ? ((ojoIzq.y - cejaIzq.y) + (ojoDer.y - cejaDer.y)) / (2 * altoRostro)
                    : 0,
                escala_rostro: Math.max(anchoOjos, altoRostro * 0.72),
                boca_x: bocaX,
                boca_y: bocaY,
                boca_z: bocaZ,
                frente_x: frente.x,
                frente_y: frente.y,
                frente_z: frente.z || 0,
                cachete_izq_x: cacheteIzq?.x || nariz.x - (anchoOjos * 0.65),
                cachete_izq_y: cacheteIzq?.y || nariz.y + (altoRostro * 0.08),
                cachete_izq_z: cacheteIzq?.z || nariz.z || 0,
                cachete_der_x: cacheteDer?.x || nariz.x + (anchoOjos * 0.65),
                cachete_der_y: cacheteDer?.y || nariz.y + (altoRostro * 0.08),
                cachete_der_z: cacheteDer?.z || nariz.z || 0,
            };
            cabezaActual = rostroActual;
            ultimoRostroVisibleEn = Date.now();
            registrarMovimientoCabeza(nariz);
        }

        if (centrado) {
            actualizarContexto(
                contextoRostro,
                bocaAbierta
                    ? "Rostro: visible, boca abierta"
                    : "Rostro: visible",
                "ok",
            );
            return;
        }

        actualizarContexto(contextoRostro, "Rostro: centre la cara", "alerta");
    });

    controladorCamara = new Camera(videoSenas, {
        onFrame: async () => {
            if (!camaraActiva) return;
            await hands.send({ image: videoSenas });
            const ahora = Date.now();
            if (pose && ahora - ultimoAnalisisTorso > 460) {
                ultimoAnalisisTorso = ahora;
                await pose.send({ image: videoSenas });
            } else if (faceMesh && ahora - ultimoAnalisisRostro > 560) {
                ultimoAnalisisRostro = ahora;
                await faceMesh.send({ image: videoSenas });
            }
        },
        width: 960,
        height: 720,
    });

    actualizarControlesCamara();
    await activarCamaraSenas();
};

// ==================== EVENTOS DEL PACIENTE: REPRODUCIR VOZ ====================
botonVozAuto?.addEventListener("click", () => {
    vozAutomaticaActiva = !vozAutomaticaActiva;
    if (!vozAutomaticaActiva && vozDisponible()) {
        colaVoz = [];
        window.speechSynthesis.cancel();
        vozLeyendo = false;
        vozPausada = false;
    }
    actualizarControlesVoz();
});

botonPausarVoz?.addEventListener("click", () => {
    if (!vozDisponible()) return;

    if (window.speechSynthesis.paused) {
        window.speechSynthesis.resume();
        vozPausada = false;
        leerSiguienteEnCola();
        actualizarControlesVoz();
        return;
    }

    if (window.speechSynthesis.speaking) {
        window.speechSynthesis.pause();
        vozPausada = true;
        actualizarControlesVoz();
    }
});

botonReiniciarVoz?.addEventListener("click", () => {
    leerEnVozAlta(textoReconocido || salida?.textContent.trim() || "");
});

botonDeshacerTranscripcion?.addEventListener("click", () => {
    fragmentosReconocidos.pop();
    ultimaSenaReconocida = fragmentosReconocidos.at(-1) || "";
    ultimaSenaReconocidaEn = 0;
    renderizarTranscripcion();
});

botonLimpiarTranscripcion?.addEventListener("click", () => {
    fragmentosReconocidos = [];
    ultimaSenaReconocida = "";
    ultimaSenaReconocidaEn = 0;
    ultimoCodigoConfirmado = "";
    permiteRepetirUltimaSena = true;
    colaVoz = [];
    if (vozDisponible()) {
        window.speechSynthesis.cancel();
        vozLeyendo = false;
        vozPausada = false;
    }
    renderizarTranscripcion();
    actualizarResultadoEnVivo(
        "Esperando una sena...",
        "El porcentaje aparecera desde la primera coincidencia.",
    );
});

// ==================== EVENTOS DEL DOCTOR: PANTALLA, TEXTO, FRASES Y MICROFONO ====================
botonPantallaPaciente?.addEventListener("click", () => {
    abrirPantallaPaciente();
    publicarMensajeDoctor(
        mensajeDoctor?.value.trim() ||
            "Pantalla del paciente lista para recibir indicaciones.",
    );
});

mensajeDoctor?.addEventListener("input", () => {
    const mensaje = mensajeDoctor.value.trim();
    if (!mensaje) return;

    if (ventanaPaciente && !ventanaPaciente.closed) {
        publicarMensajeDoctor(mensaje);
    }
});

document.querySelectorAll("[data-frase-doctor]").forEach((boton) => {
    boton.addEventListener("click", () => {
        if (!mensajeDoctor) return;

        mensajeDoctor.value = boton.dataset.fraseDoctor;
        abrirPantallaPaciente();
        publicarMensajeDoctor(mensajeDoctor.value.trim());
    });
});

botonMicrofono?.addEventListener("click", () => {
    microfonoActivo = !microfonoActivo;
    botonMicrofono.dataset.activo = String(microfonoActivo);
    botonMicrofono.textContent = microfonoActivo
        ? "Desactivar microfono"
        : "Activar microfono";

    if (microfonoActivo) {
        abrirPantallaPaciente();
        if (mensajeDoctor) {
            mensajeDoctor.value =
                "Doctor hablando: necesito revisar la zona afectada y explicarle el tratamiento.";
            publicarMensajeDoctor(mensajeDoctor.value.trim());
        }
        return;
    }

    publicarMensajeDoctor(
        "Microfono desactivado. El doctor detuvo la comunicacion por voz.",
    );
});

formDoctor?.addEventListener("submit", (event) => {
    event.preventDefault();
    enviarAPantallaPaciente();
});

botonesEntrenamiento.forEach((boton) => {
    boton.addEventListener("click", () => {
        guardarMuestraEntrenamiento(boton.dataset.entrenarSena);
    });
});

botonConfirmarSena?.addEventListener("click", async () => {
    if (!ultimaPrediccionSena?.codigo) {
        actualizarEstadoEntrenamiento(
            "La captura ya no esta disponible. Repita la sena para detectarla nuevamente.",
            "error",
        );
        return;
    }

    if (Date.now() > ultimaPrediccionSena.expiraEn) {
        limpiarCapturaPrediccion();
        actualizarEstadoEntrenamiento(
            "La captura vencio. Repita la sena para generar una nueva.",
            "error",
        );
        return;
    }

    const guardado = await guardarMuestraEntrenamiento(
        ultimaPrediccionSena.codigo,
        ultimaPrediccionSena.texto || "",
        ultimaPrediccionSena.secuencia,
    );
    if (guardado) limpiarCapturaPrediccion();
});

botonCorregirSena?.addEventListener("click", () => {
    pausarReconocimientoParaEntrenamiento();
    panelCorreccion?.classList.remove("oculto");
    if (fraseCorreccion) fraseCorreccion.value = "";
    fraseCorreccion?.focus();
    cargarFrasesAprendidas();
    actualizarEstadoEntrenamiento(
        "Escriba o busque la frase correcta y guarde la correccion.",
        "error",
    );
});

const retirarPrediccionAgregada = (prediccion) => {
    const textoDescartado = prediccion?.texto?.trim() || "";
    const ultimoFragmento = fragmentosReconocidos.at(-1)?.trim() || "";

    if (
        prediccion?.agregadaAlTexto &&
        textoDescartado &&
        ultimoFragmento.localeCompare(textoDescartado, undefined, {
            sensitivity: "base",
        }) === 0
    ) {
        fragmentosReconocidos.pop();
        ultimaSenaReconocida = fragmentosReconocidos.at(-1) || "";
        ultimaSenaReconocidaEn = 0;
        renderizarTranscripcion();
    }
};

const limpiarValidacionRechazada = (prediccion) => {
    retirarPrediccionAgregada(prediccion);

    if (prediccion?.codigo) {
        ultimaEmisionPorCodigo.delete(prediccion.codigo);
    }
    ultimoCodigoConfirmado = "";
    permiteRepetirUltimaSena = true;
    limpiarCapturaPrediccion();
    panelCorreccion?.classList.add("oculto");
    secuenciaCorreccion = [];
    capturandoCorreccion = false;
    reanudarReconocimientoDespuesDeEntrenar();
};

botonNingunaSena?.addEventListener("click", async () => {
    const prediccion = ultimaPrediccionSena;
    if (!prediccion?.secuencia?.length) {
        actualizarEstadoEntrenamiento(
            "La captura ya no esta disponible para aprenderla como ninguna sena.",
            "error",
        );
        return;
    }

    pausarReconocimientoParaEntrenamiento();
    const guardado = await guardarMuestraEntrenamiento(
        CODIGO_NINGUNA_SENA,
        "Ninguna sena",
        prediccion.secuencia,
    );
    if (!guardado) return;

    limpiarValidacionRechazada(prediccion);
    actualizarEstadoEntrenamiento(
        "Aprendido como ninguna sena. Movimientos parecidos se rechazaran en adelante.",
        "ok",
    );
    actualizarResultadoEnVivo(
        "Ninguna sena",
        "El ejemplo negativo fue guardado correctamente.",
        "esperando",
    );
});

botonDescartarSena?.addEventListener("click", () => {
    const prediccionDescartada = ultimaPrediccionSena;
    limpiarValidacionRechazada(prediccionDescartada);
    actualizarEstadoEntrenamiento(
        "Prediccion descartada. No se guardo ninguna muestra.",
        "ok",
    );
    actualizarResultadoEnVivo(
        "Ninguna coincidencia seleccionada",
        "La captura fue descartada y el reconocimiento continua.",
        "esperando",
    );
});

botonIniciarGrabacion?.addEventListener("click", iniciarGrabacionFrase);
botonGuardarFraseEntrenada?.addEventListener("click", finalizarGrabacionFrase);
botonToggleEntrenamiento?.addEventListener("click", alternarModoEntrenamiento);
botonGuardarCorreccion?.addEventListener("click", guardarCorreccionLibre);
botonToggleCamara?.addEventListener("click", () => {
    if (camaraActiva) {
        desactivarCamaraSenas();
        return;
    }

    activarCamaraSenas();
});
botonEnfocarCamara?.addEventListener("click", enfocarCamaraSenas);
fraseCorreccion?.addEventListener("input", () => {
    window.clearTimeout(temporizadorBusquedaFrases);
    temporizadorBusquedaFrases = window.setTimeout(() => {
        cargarFrasesAprendidas(fraseCorreccion.value);
    }, 250);
});

// ==================== INICIALIZACION DEL TRADUCTOR ====================
window.addEventListener("load", () => {
    renderizarTranscripcion();
    iniciarReconocimientoReal();
});
