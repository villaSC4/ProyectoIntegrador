// ==================== DATOS SIMULADOS DE PACIENTES ====================
const pacientes = {
  lucia: {
    nombre: "Lucia Fernandez Quispe",
    dni: "71234567",
    edad: "27",
    bmi: "23.4",
    condicion: "Paciente sordomuda",
    especialidad: "Dermatología",
    motivo: "Tratamiento de manchas",
    diagnostico:
      "Manchas faciales superficiales. Se recomienda control dermatologico y proteccion solar estricta.",
    historial: [
      [
        "31 May",
        "Consulta dermatológica",
        "Evaluacion de manchas y sensibilidad facial.",
      ],
      [
        "12 May",
        "Registro facial validado",
        "Identidad confirmada en el modulo de acceso de pacientes.",
      ],
      [
        "04 May",
        "Observacion medica",
        "Paciente reporta irritacion leve posterior a limpieza facial.",
      ],
    ],
    pendientes: [
      "Confirmar alergias",
      "Revisar tolerancia a productos tópicos",
      "Registrar evolucion al cierre",
    ],
  },
  marco: {
    nombre: "Marco Salazar Rojas",
    dni: "76543210",
    edad: "34",
    bmi: "26.1",
    condicion: "Paciente regular",
    especialidad: "Dermatología",
    motivo: "Revisión dermatológica",
    diagnostico:
      "Lesiones leves sin signos de alarma. Requiere seguimiento y control preventivo.",
    historial: [
      ["31 May", "Consulta programada", "Revision dermatologica de control."],
      [
        "10 May",
        "Control previo",
        "Se recomendo hidratacion y observacion de lesiones.",
      ],
      ["21 Abr", "Admision", "Paciente registrado por ingreso manual."],
    ],
    pendientes: [
      "Revisar lesiones nuevas",
      "Actualizar indicaciones",
      "Programar control",
    ],
  },
  rosa: {
    nombre: "Rosa Huaman Torres",
    dni: "73450192",
    edad: "41",
    bmi: "24.7",
    condicion: "Paciente sordomuda",
    especialidad: "Dermatología",
    motivo: "Acné severo",
    diagnostico:
      "Acne inflamatorio persistente. Necesita tratamiento progresivo y explicacion visual de indicaciones.",
    historial: [
      [
        "31 May",
        "Consulta programada",
        "Revision dermatologica con apoyo de traductor visual.",
      ],
      [
        "18 May",
        "Registro de sintomas",
        "Paciente indico dolor e inflamacion mediante modulo de señas.",
      ],
      ["02 May", "Actualizacion", "Se agrego nota de sensibilidad cutanea."],
    ],
    pendientes: [
      "Confirmar zona afectada",
      "Revisar tratamiento anterior",
      "Anotar evolucion",
    ],
  },
  diego: {
    nombre: "Diego Ramos Castillo",
    dni: "70124590",
    edad: "22",
    bmi: "21.9",
    condicion: "Paciente regular",
    especialidad: "Dermatología",
    motivo: "Limpieza facial",
    diagnostico:
      "Evaluacion inicial para limpieza facial. No presenta historial dermatologico previo.",
    historial: [
      ["31 May", "Primera consulta", "Paciente asignado al turno de mañana."],
      ["27 May", "Pre registro", "Datos basicos validados en admision."],
    ],
    pendientes: [
      "Completar antecedentes",
      "Evaluar tipo de piel",
      "Registrar diagnostico inicial",
    ],
  },
};

// ==================== DATOS SIMULADOS DE ANOTACIONES MEDICAS ====================
let anotaciones = [
  {
    id: crearId(),
    titulo: "Control post tratamiento",
    fecha: "2026-05-25",
    modificado: "2026-05-29",
    descripcion:
      "Paciente refiere mejoria posterior al tratamiento inicial. Mantiene leve sensibilidad en mejillas.",
    diagnostico: "Evolucion favorable de manchas superficiales.",
    tratamiento: "Continuar protector solar y crema nocturna por 14 dias.",
    observaciones: "Evitar exfoliacion agresiva.",
    estado: "En seguimiento",
    doctor: "Dr. Carlos Rivas",
  },
  {
    id: crearId(),
    titulo: "Control dermatológico inicial",
    fecha: "2026-05-12",
    modificado: "2026-05-12",
    descripcion:
      "Primera revision dermatologica. Se observa irritacion leve y resequedad.",
    diagnostico: "Dermatitis leve por producto cosmetico.",
    tratamiento: "Suspender producto irritante e hidratar la zona.",
    observaciones: "Revisar evolucion en siguiente cita.",
    estado: "Finalizado",
    doctor: "Dr. Carlos Rivas",
  },
];

// ==================== DATOS SIMULADOS DE RECETAS POR PACIENTE ====================
const recetasPorPaciente = {
  lucia: [
    {
      id: crearId(),
      numero: "RX-000124",
      fecha: "2026-06-02",
      vigencia: "2026-07-02",
      especialidad: "Dermatologia",
      diagnostico: "Manchas faciales superficiales",
      observaciones:
        "Evitar exposicion solar directa y regresar a control si presenta irritacion.",
      doctor: "Dr. Carlos Rivas",
      medicamentos: [
        {
          nombre: "Hidroquinona",
          presentacion: "Crema 4%",
          via: "Topica",
          dosis: "Capa fina",
          frecuencia: "Noche",
          duracion: "14 dias",
          cantidad: "1 tubo",
          indicaciones: "Aplicar solo en la zona indicada.",
        },
        {
          nombre: "Protector solar",
          presentacion: "FPS 50+",
          via: "Topica",
          dosis: "Aplicacion generosa",
          frecuencia: "Cada 3 horas",
          duracion: "30 dias",
          cantidad: "1 frasco",
          indicaciones: "Usar incluso si permanece en interiores.",
        },
      ],
    },
  ],
  marco: [
    {
      id: crearId(),
      numero: "RX-000125",
      fecha: "2026-06-02",
      vigencia: "2026-06-17",
      especialidad: "Dermatologia",
      diagnostico: "Lesiones leves sin signos de alarma",
      observaciones: "Mantener hidratacion cutanea y observar cambios.",
      doctor: "Dr. Carlos Rivas",
      medicamentos: [
        {
          nombre: "Crema hidratante",
          presentacion: "Piel sensible",
          via: "Topica",
          dosis: "Capa fina",
          frecuencia: "Manana y noche",
          duracion: "15 dias",
          cantidad: "1 tubo",
          indicaciones: "Aplicar despues de lavar la zona.",
        },
      ],
    },
  ],
  rosa: [],
  diego: [],
};

// ==================== ESTADO ACTUAL DE LA PANTALLA ====================
let pacienteActualKey = "lucia";
let anotacionSeleccionada = anotaciones[0]?.id;
let recetaSeleccionada = recetasPorPaciente.lucia[0]?.id || null;
let modoVista = "rows";
let editandoId = null;
let editandoRecetaId = null;
let medicamentosTemporales = [];

// ==================== UTILIDADES GENERALES ====================
function crearId() {
  if (window.crypto?.randomUUID) return window.crypto.randomUUID();
  return `nota-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function formatFecha(fecha) {
  return new Date(`${fecha}T00:00:00`).toLocaleDateString("es-PE");
}

function fechaActualInput() {
  const hoy = new Date();
  return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;
}

// ==================== RENDER: DATOS, HISTORIAL Y PENDIENTES DEL PACIENTE ====================
function renderPaciente() {
  const params = new URLSearchParams(window.location.search);
  const key = params.get("paciente") || "lucia";
  const paciente = pacientes[key] || pacientes.lucia;
  pacienteActualKey = pacientes[key] ? key : "lucia";
  recetaSeleccionada =
    recetasPorPaciente[pacienteActualKey]?.[0]?.id || null;

  document
    .querySelectorAll("[data-paciente-nombre]")
    .forEach((item) => (item.textContent = paciente.nombre));
  document
    .querySelectorAll("[data-paciente-condicion]")
    .forEach((item) => (item.textContent = paciente.condicion));

  document.querySelector("[data-paciente-detalle]").innerHTML = `
    <div><span>DNI</span><strong>${paciente.dni}</strong></div>
    <div><span>Edad</span><strong>${paciente.edad}</strong></div>
    <div><span>BMI</span><strong>${paciente.bmi}</strong></div>
    <div><span>Especialidad</span><strong>${paciente.especialidad}</strong></div>
    <div><span>Motivo</span><strong>${paciente.motivo}</strong></div>
  `;

  document.querySelector("[data-diagnostico]").textContent =
    paciente.diagnostico;
  document.querySelector("[data-historial]").innerHTML = paciente.historial
    .map(
      ([fecha, titulo, texto]) => `
    <div class="historial-item">
      <time>${fecha}</time>
      <div><strong>${titulo}</strong><p>${texto}</p></div>
    </div>
  `,
    )
    .join("");
  document.querySelector("[data-pendientes]").innerHTML = paciente.pendientes
    .map((pendiente) => `<p class="diagnostico">${pendiente}</p>`)
    .join("");
}

// ==================== ANOTACIONES: TARJETAS Y LISTA DE ARCHIVOS ====================
function archivoTemplate(anotacion) {
  return `
    <div class="archivo-item ${anotacion.id === anotacionSeleccionada ? "activo" : ""}" data-id="${anotacion.id}">
      <div class="archivo-icono">DOC</div>
      <div class="archivo-contenido">
        <h4>${anotacion.titulo}</h4>
        <small>Creado: ${formatFecha(anotacion.fecha)}</small>
        <small>Modificado: ${formatFecha(anotacion.modificado || anotacion.fecha)}</small>
        <div class="archivo-estado">${anotacion.estado}</div>
      </div>
    </div>
  `;
}

function renderArchivos() {
  const lista = document.querySelector("[data-archivos-lista]");
  lista.className = `archivos-lista vista-${modoVista === "rows" ? "filas" : modoVista === "columns" ? "columnas" : "agrupada"}`;

  if (modoVista === "grouped") {
    const grupos = anotaciones.reduce((acc, item) => {
      const fecha = new Date(`${item.fecha}T00:00:00`);
      const clave = fecha.toLocaleDateString("es-PE", {
        month: "long",
        year: "numeric",
      });
      acc[clave] = acc[clave] || [];
      acc[clave].push(item);
      return acc;
    }, {});

    lista.innerHTML = Object.entries(grupos)
      .map(
        ([grupo, items]) => `
      <section class="grupo-anotaciones">
        <h4>${grupo}</h4>
        <small>${items.length} archivo(s)</small>
        ${items.map(archivoTemplate).join("")}
      </section>
    `,
      )
      .join("");
  } else {
    lista.innerHTML = anotaciones.map(archivoTemplate).join("");
  }

  lista.querySelectorAll("[data-id]").forEach((item) => {
    item.addEventListener("click", () => {
      anotacionSeleccionada = item.dataset.id;
      renderArchivos();
      renderPreview();
    });
  });
}

// ==================== ANOTACIONES: PREVIEW, FORMULARIO, VER E IMPRIMIR ====================
function renderPreview() {
  const anotacion = anotaciones.find(
    (item) => item.id === anotacionSeleccionada,
  );
  if (!anotacion) return;

  document.querySelector("[data-preview-titulo]").textContent =
    anotacion.titulo;
  document.querySelector("[data-preview-meta]").textContent =
    `${formatFecha(anotacion.fecha)} · Estado: ${anotacion.estado}`;
  document.querySelector("[data-preview-diagnostico]").textContent =
    anotacion.diagnostico;
  document.querySelector("[data-preview-descripcion]").textContent =
    `${anotacion.descripcion.slice(0, 120)}...`;
  document.querySelector("[data-preview-doctor]").textContent =
    anotacion.doctor;
}

function abrirFormulario(anotacion = null) {
  const modal = document.querySelector("[data-modal-anotacion]");
  const form = document.querySelector("[data-form-anotacion]");
  editandoId = anotacion?.id || null;
  document.querySelector("[data-modal-titulo]").textContent = anotacion
    ? "Editar Anotación"
    : "Nueva Anotación";

  form.titulo.value = anotacion?.titulo || "";
  form.fecha.value = anotacion?.fecha || fechaActualInput();
  form.descripcion.value = anotacion?.descripcion || "";
  form.diagnostico.value = anotacion?.diagnostico || "";
  form.tratamiento.value = anotacion?.tratamiento || "";
  form.observaciones.value = anotacion?.observaciones || "";
  form.estado.value = anotacion?.estado || "Pendiente";
  form.doctor.value = anotacion?.doctor || "Dr. Carlos Rivas";

  modal.classList.remove("oculto");
}

function cerrarFormulario() {
  document.querySelector("[data-modal-anotacion]").classList.add("oculto");
}

function verArchivo() {
  const anotacion = anotaciones.find(
    (item) => item.id === anotacionSeleccionada,
  );
  if (!anotacion) return;

  document.querySelector("[data-ver-titulo]").textContent = anotacion.titulo;
  document.querySelector("[data-ver-meta]").textContent =
    `${formatFecha(anotacion.fecha)} · ${anotacion.estado}`;
  document.querySelector("[data-ver-contenido]").innerHTML = `
    <article><strong>Descripción médica</strong><p>${anotacion.descripcion}</p></article>
    <article><strong>Diagnóstico</strong><p>${anotacion.diagnostico}</p></article>
    <article><strong>Tratamiento recomendado</strong><p>${anotacion.tratamiento}</p></article>
    <article><strong>Observaciones adicionales</strong><p>${anotacion.observaciones || "Sin observaciones adicionales."}</p></article>
    <article><strong>Doctor responsable</strong><p>${anotacion.doctor}</p></article>
  `;
  document.querySelector("[data-modal-ver]").classList.remove("oculto");
}

function imprimirArchivo() {
  const anotacion = anotaciones.find(
    (item) => item.id === anotacionSeleccionada,
  );
  if (!anotacion) return;

  const ventana = window.open("", "impresionAnotacion", "width=820,height=720");
  if (!ventana) return;

  ventana.document.write(`
    <!doctype html>
    <html lang="es">
      <head>
        <meta charset="UTF-8" />
        <title>${anotacion.titulo}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #173436; margin: 34px; }
          h1 { margin-bottom: 6px; font-size: 26px; }
          .meta { color: #64787c; margin-bottom: 24px; }
          article { border: 1px solid #d9e8e7; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
          strong { display: block; margin-bottom: 6px; }
          p { margin: 0; line-height: 1.6; }
        </style>
      </head>
      <body>
        <h1>${anotacion.titulo}</h1>
        <p class="meta">${formatFecha(anotacion.fecha)} · Estado: ${anotacion.estado}</p>
        <article><strong>Descripcion medica</strong><p>${anotacion.descripcion}</p></article>
        <article><strong>Diagnostico</strong><p>${anotacion.diagnostico}</p></article>
        <article><strong>Tratamiento recomendado</strong><p>${anotacion.tratamiento}</p></article>
        <article><strong>Observaciones adicionales</strong><p>${anotacion.observaciones || "Sin observaciones adicionales."}</p></article>
        <article><strong>Doctor responsable</strong><p>${anotacion.doctor}</p></article>
      </body>
    </html>
  `);
  ventana.document.close();
  ventana.focus();
  ventana.print();
}

// ==================== RECETAS: HELPERS DEL PACIENTE ACTUAL ====================
function recetasPacienteActual() {
  recetasPorPaciente[pacienteActualKey] =
    recetasPorPaciente[pacienteActualKey] || [];
  return recetasPorPaciente[pacienteActualKey];
}

function recetaActual() {
  return recetasPacienteActual().find((item) => item.id === recetaSeleccionada);
}

// ==================== RECETAS: LISTA Y PREVIEW ====================
function recetaTemplate(receta) {
  const medicamentos = receta.medicamentos
    .map(
      (item, index) => `
        <tr>
          <td>${index + 1}</td>
          <td>${item.nombre}</td>
          <td>${item.presentacion}</td>
          <td>${item.via}</td>
          <td>${item.cantidad}</td>
        </tr>
      `,
    )
    .join("");

  return `
    <article class="receta-item ${receta.id === recetaSeleccionada ? "activo" : ""}" data-receta-id="${receta.id}">
      <div class="receta-item-cabecera">
        <div>
          <h4>${receta.numero} - ${receta.diagnostico}</h4>
          <small>Emitida: ${formatFecha(receta.fecha)}</small>
          <small>Vigencia: ${receta.vigencia ? formatFecha(receta.vigencia) : "Sin vigencia definida"}</small>
        </div>
        <span class="etiqueta">${receta.medicamentos.length} med.</span>
      </div>
      <table class="receta-tabla-mini">
        <thead>
          <tr><th>N</th><th>Medicamento</th><th>Presentacion</th><th>Via</th><th>Cantidad</th></tr>
        </thead>
        <tbody>${medicamentos}</tbody>
      </table>
    </article>
  `;
}

function renderRecetas() {
  const lista = document.querySelector("[data-recetas-lista]");
  const recetas = recetasPacienteActual();

  if (!recetas.length) {
    lista.innerHTML = `<div class="diagnostico">Este paciente aun no tiene recetas registradas.</div>`;
    renderPreviewReceta();
    return;
  }

  if (!recetaSeleccionada || !recetas.some((item) => item.id === recetaSeleccionada)) {
    recetaSeleccionada = recetas[0].id;
  }

  lista.innerHTML = recetas.map(recetaTemplate).join("");
  lista.querySelectorAll("[data-receta-id]").forEach((item) => {
    item.addEventListener("click", () => {
      recetaSeleccionada = item.dataset.recetaId;
      renderRecetas();
      renderPreviewReceta();
    });
  });

  renderPreviewReceta();
}

function renderPreviewReceta() {
  const receta = recetaActual();

  if (!receta) {
    document.querySelector("[data-receta-titulo]").textContent =
      "Seleccione una receta";
    document.querySelector("[data-receta-meta]").textContent =
      "La vista previa aparecera aqui.";
    document.querySelector("[data-receta-diagnostico]").textContent = "-";
    document.querySelector("[data-receta-medicamentos]").innerHTML = "-";
    return;
  }

  document.querySelector("[data-receta-titulo]").textContent = receta.numero;
  document.querySelector("[data-receta-meta]").textContent =
    `${formatFecha(receta.fecha)} · ${receta.especialidad}`;
  document.querySelector("[data-receta-diagnostico]").textContent =
    receta.diagnostico;
  document.querySelector("[data-receta-medicamentos]").innerHTML = receta.medicamentos
    .map(
      (item) =>
        `<span><strong>${item.nombre}</strong> - ${item.presentacion} · ${item.cantidad}</span>`,
    )
    .join("");
}

// ==================== RECETAS: FORMULARIO DINAMICO DE MEDICAMENTOS ====================
function renderMedicamentosForm() {
  const contenedor = document.querySelector("[data-medicamentos-form]");
  contenedor.innerHTML = medicamentosTemporales
    .map(
      (medicamento, index) => `
        <div class="medicamento-form-item" data-medicamento-index="${index}">
          <label>Medicamento<input type="text" data-campo="nombre" value="${medicamento.nombre}" required /></label>
          <label>Presentacion <span class="campo-ayuda">Opcional</span><input type="text" data-campo="presentacion" value="${medicamento.presentacion}" /></label>
          <label>Via <span class="campo-ayuda">Opcional</span><input type="text" data-campo="via" value="${medicamento.via}" /></label>
          <label>Dosis<input type="text" data-campo="dosis" value="${medicamento.dosis}" required /></label>
          <label>Frecuencia<input type="text" data-campo="frecuencia" value="${medicamento.frecuencia}" required /></label>
          <label>Duracion<input type="text" data-campo="duracion" value="${medicamento.duracion}" required /></label>
          <label>Cantidad<input type="text" data-campo="cantidad" value="${medicamento.cantidad}" required /></label>
          <label>Indicaciones <span class="campo-ayuda">Opcional</span><input type="text" data-campo="indicaciones" value="${medicamento.indicaciones}" /></label>
          <button class="boton boton-secundario boton-peligro" type="button" data-quitar-medicamento="${index}">Quitar medicamento</button>
        </div>
      `,
    )
    .join("");

  contenedor.querySelectorAll("input").forEach((input) => {
    input.addEventListener("input", () => {
      const item = input.closest("[data-medicamento-index]");
      medicamentosTemporales[Number(item.dataset.medicamentoIndex)][
        input.dataset.campo
      ] = input.value;
    });
  });

  contenedor.querySelectorAll("[data-quitar-medicamento]").forEach((boton) => {
    boton.addEventListener("click", () => {
      if (medicamentosTemporales.length === 1) return;
      medicamentosTemporales.splice(Number(boton.dataset.quitarMedicamento), 1);
      renderMedicamentosForm();
    });
  });
}

// ==================== RECETAS: CREAR / EDITAR / GUARDAR ====================
function medicamentoVacio() {
  return {
    nombre: "",
    presentacion: "",
    via: "Oral",
    dosis: "",
    frecuencia: "",
    duracion: "",
    cantidad: "",
    indicaciones: "",
  };
}

function abrirFormularioReceta(receta = null) {
  const paciente = pacientes[pacienteActualKey] || pacientes.lucia;
  const form = document.querySelector("[data-form-receta]");
  editandoRecetaId = receta?.id || null;
  medicamentosTemporales = receta
    ? receta.medicamentos.map((item) => ({ ...item }))
    : [medicamentoVacio()];

  document.querySelector("[data-receta-modal-titulo]").textContent = receta
    ? "Editar Receta"
    : "Nueva Receta";
  form.fecha.value = receta?.fecha || fechaActualInput();
  form.vigencia.value = receta?.vigencia || "";
  form.especialidad.value = receta?.especialidad || paciente.especialidad;
  form.doctor.value = receta?.doctor || "Dr. Carlos Rivas";
  form.diagnostico.value = receta?.diagnostico || paciente.motivo;
  form.observaciones.value = receta?.observaciones || "";

  renderMedicamentosForm();
  document.querySelector("[data-modal-receta]").classList.remove("oculto");
}

function cerrarFormularioReceta() {
  document.querySelector("[data-modal-receta]").classList.add("oculto");
}

function numeroReceta() {
  return `RX-${String(Date.now()).slice(-6)}`;
}

function guardarReceta(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const receta = {
    id: editandoRecetaId || crearId(),
    numero:
      recetasPacienteActual().find((item) => item.id === editandoRecetaId)
        ?.numero || numeroReceta(),
    fecha: form.fecha.value,
    vigencia: form.vigencia.value,
    especialidad: form.especialidad.value,
    diagnostico: form.diagnostico.value,
    observaciones: form.observaciones.value,
    doctor: form.doctor.value,
    medicamentos: medicamentosTemporales.map((item) => ({ ...item })),
  };

  if (editandoRecetaId) {
    recetasPorPaciente[pacienteActualKey] = recetasPacienteActual().map((item) =>
      item.id === editandoRecetaId ? receta : item,
    );
  } else {
    recetasPacienteActual().unshift(receta);
  }

  recetaSeleccionada = receta.id;
  cerrarFormularioReceta();
  renderRecetas();
}

// ==================== RECETAS: TABLA COMPACTA, VER E IMPRIMIR ====================
function tablaMedicamentos(receta) {
  return `
    <table>
      <thead>
        <tr>
          <th>N</th>
          <th>Medicamento</th>
          <th>Presentacion</th>
          <th>Via</th>
          <th>Dosis</th>
          <th>Frecuencia</th>
          <th>Duracion</th>
          <th>Cantidad</th>
        </tr>
      </thead>
      <tbody>
        ${receta.medicamentos
          .map(
            (item, index) => `
          <tr>
            <td>${index + 1}</td>
            <td>${item.nombre}</td>
            <td>${item.presentacion}</td>
            <td>${item.via}</td>
            <td>${item.dosis}</td>
            <td>${item.frecuencia}</td>
            <td>${item.duracion}</td>
            <td>${item.cantidad}</td>
          </tr>
        `,
          )
          .join("")}
      </tbody>
    </table>
  `;
}

function indicacionesMedicamentos(receta) {
  return receta.medicamentos
    .filter((item) => item.indicaciones)
    .map((item, index) => `<p>${index + 1}. ${item.nombre}: ${item.indicaciones}</p>`)
    .join("");
}

function verReceta() {
  const receta = recetaActual();
  if (!receta) return;

  document.querySelector("[data-ver-titulo]").textContent = receta.numero;
  document.querySelector("[data-ver-meta]").textContent =
    `${formatFecha(receta.fecha)} · ${receta.especialidad}`;
  document.querySelector("[data-ver-contenido]").innerHTML = `
    <article><strong>Diagnostico relacionado</strong><p>${receta.diagnostico}</p></article>
    <article><strong>Medicamentos</strong>${tablaMedicamentos(receta)}</article>
    <article><strong>Indicaciones por medicamento</strong>${indicacionesMedicamentos(receta) || "<p>Sin indicaciones especificas.</p>"}</article>
    <article><strong>Indicaciones generales</strong><p>${receta.observaciones || "Sin indicaciones generales."}</p></article>
    <article><strong>Doctor responsable</strong><p>${receta.doctor}</p></article>
  `;
  document.querySelector("[data-modal-ver]").classList.remove("oculto");
}

function imprimirReceta() {
  const receta = recetaActual();
  const paciente = pacientes[pacienteActualKey] || pacientes.lucia;
  if (!receta) return;

  const ventana = window.open("", "impresionReceta", "width=980,height=720");
  if (!ventana) return;

  ventana.document.write(`
    <!doctype html>
    <html lang="es">
      <head>
        <meta charset="UTF-8" />
        <title>${receta.numero}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #173436; margin: 28px; font-size: 12px; }
          h1 { margin: 0 0 8px; font-size: 22px; }
          .cabecera { display: flex; justify-content: space-between; gap: 18px; border-bottom: 2px solid #173436; padding-bottom: 10px; margin-bottom: 12px; }
          .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 18px; margin-bottom: 12px; }
          .bloque { border: 1px solid #d9e8e7; border-radius: 6px; padding: 10px; margin-bottom: 10px; }
          table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
          th, td { border: 1px solid #b9cccc; padding: 6px; text-align: left; vertical-align: top; }
          th { background: #eef7f5; }
          p { margin: 4px 0; line-height: 1.45; }
          .firma { display: flex; justify-content: flex-end; margin-top: 42px; }
          .firma div { width: 260px; text-align: center; border-top: 1px solid #173436; padding-top: 8px; }
        </style>
      </head>
      <body>
        <div class="cabecera">
          <div>
            <h1>MediSign - Receta Medica</h1>
            <p>Receta N: ${receta.numero}</p>
          </div>
          <div>
            <p><strong>Fecha:</strong> ${formatFecha(receta.fecha)}</p>
            <p><strong>Vigencia:</strong> ${receta.vigencia ? formatFecha(receta.vigencia) : "No definida"}</p>
          </div>
        </div>
        <section class="datos">
          <p><strong>Paciente:</strong> ${paciente.nombre}</p>
          <p><strong>DNI:</strong> ${paciente.dni}</p>
          <p><strong>Doctor:</strong> ${receta.doctor}</p>
          <p><strong>Especialidad:</strong> ${receta.especialidad}</p>
        </section>
        <section class="bloque"><strong>Diagnostico:</strong> ${receta.diagnostico}</section>
        <section class="bloque">
          <strong>Medicamentos recetados</strong>
          ${tablaMedicamentos(receta)}
        </section>
        <section class="bloque">
          <strong>Indicaciones</strong>
          ${indicacionesMedicamentos(receta) || "<p>Seguir los medicamentos segun indicacion medica.</p>"}
          <p>${receta.observaciones || ""}</p>
        </section>
        <section class="firma"><div>Firma y sello del medico<br />${receta.doctor}</div></section>
      </body>
    </html>
  `);
  ventana.document.close();
  ventana.focus();
  ventana.print();
}

// ==================== EVENTOS DE ANOTACIONES MEDICAS ====================
document
  .querySelector("[data-nueva-anotacion]")
  .addEventListener("click", () => abrirFormulario());
document
  .querySelector("[data-cerrar-modal]")
  .addEventListener("click", cerrarFormulario);
document
  .querySelector("[data-cancelar-modal]")
  .addEventListener("click", cerrarFormulario);
document
  .querySelector("[data-cerrar-ver]")
  .addEventListener("click", () =>
    document.querySelector("[data-modal-ver]").classList.add("oculto"),
  );
document
  .querySelector("[data-ver-anotacion]")
  .addEventListener("click", verArchivo);
document
  .querySelector("[data-imprimir-anotacion]")
  .addEventListener("click", imprimirArchivo);
document
  .querySelector("[data-editar-anotacion]")
  .addEventListener("click", () =>
    abrirFormulario(
      anotaciones.find((item) => item.id === anotacionSeleccionada),
    ),
  );
document
  .querySelector("[data-eliminar-anotacion]")
  .addEventListener("click", () => {
    if (!anotacionSeleccionada) return;
    if (!confirm("¿Está seguro de eliminar esta anotación médica?")) return;
    anotaciones = anotaciones.filter(
      (item) => item.id !== anotacionSeleccionada,
    );
    anotacionSeleccionada = anotaciones[0]?.id || null;
    renderArchivos();
    renderPreview();
  });

// ==================== EVENTOS DE RECETAS MEDICAS ====================
document
  .querySelector("[data-nueva-receta]")
  .addEventListener("click", () => abrirFormularioReceta());
document
  .querySelector("[data-cerrar-receta]")
  .addEventListener("click", cerrarFormularioReceta);
document
  .querySelector("[data-cancelar-receta]")
  .addEventListener("click", cerrarFormularioReceta);
document
  .querySelector("[data-agregar-medicamento]")
  .addEventListener("click", () => {
    medicamentosTemporales.push(medicamentoVacio());
    renderMedicamentosForm();
  });
document.querySelector("[data-ver-receta]").addEventListener("click", verReceta);
document
  .querySelector("[data-imprimir-receta]")
  .addEventListener("click", imprimirReceta);
document
  .querySelector("[data-editar-receta]")
  .addEventListener("click", () => abrirFormularioReceta(recetaActual()));
document
  .querySelector("[data-eliminar-receta]")
  .addEventListener("click", () => {
    if (!recetaSeleccionada) return;
    if (!confirm("Â¿EstÃ¡ seguro de eliminar esta receta mÃ©dica?")) return;
    recetasPorPaciente[pacienteActualKey] = recetasPacienteActual().filter(
      (item) => item.id !== recetaSeleccionada,
    );
    recetaSeleccionada = recetasPacienteActual()[0]?.id || null;
    renderRecetas();
  });

// ==================== EVENTOS DE VISTA DE ANOTACIONES ====================
document.querySelectorAll("[data-view]").forEach((boton) => {
  boton.addEventListener("click", () => {
    modoVista = boton.dataset.view;
    document
      .querySelectorAll("[data-view]")
      .forEach((item) => item.classList.remove("activo"));
    boton.classList.add("activo");
    renderArchivos();
  });
});

// ==================== GUARDADO DE FORMULARIOS ====================
document
  .querySelector("[data-form-anotacion]")
  .addEventListener("submit", (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const datos = {
      id: editandoId || crearId(),
      titulo: form.titulo.value,
      fecha: form.fecha.value,
      modificado: fechaActualInput(),
      descripcion: form.descripcion.value,
      diagnostico: form.diagnostico.value,
      tratamiento: form.tratamiento.value,
      observaciones: form.observaciones.value,
      estado: form.estado.value,
      doctor: form.doctor.value,
    };

    if (editandoId) {
      anotaciones = anotaciones.map((item) =>
        item.id === editandoId ? datos : item,
      );
    } else {
      anotaciones.unshift(datos);
    }

    anotacionSeleccionada = datos.id;
    cerrarFormulario();
    renderArchivos();
    renderPreview();
  });

document
  .querySelector("[data-form-receta]")
  .addEventListener("submit", guardarReceta);

// ==================== INICIALIZACION DE LA PANTALLA ====================
renderPaciente();
renderArchivos();
renderPreview();
renderRecetas();
