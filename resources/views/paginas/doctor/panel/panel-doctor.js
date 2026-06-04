// ==================== DATOS SIMULADOS DE PACIENTES ====================
const pacientes = [
  {
    id: "lucia",
    nombre: "Lucia Fernandez Quispe",
    dni: "71234567",
    tipo: "Paciente sordomudo",
    area: "Dermatologia",
    motivo: "Tratamiento de manchas"
  },
  {
    id: "marco",
    nombre: "Marco Salazar Rojas",
    dni: "76543210",
    tipo: "Paciente regular",
    area: "Dermatologia",
    motivo: "Revision dermatologica"
  },
  {
    id: "rosa",
    nombre: "Rosa Huaman Torres",
    dni: "73450192",
    tipo: "Paciente sordomudo",
    area: "Dermatologia",
    motivo: "Acne severo"
  },
  {
    id: "diego",
    nombre: "Diego Ramos Castillo",
    dni: "70124590",
    tipo: "Paciente regular",
    area: "Dermatologia",
    motivo: "Limpieza facial"
  },
  {
    id: "ana",
    nombre: "Ana Paula Cardenas Vega",
    dni: "78451236",
    tipo: "Paciente regular",
    area: "Dermatologia",
    motivo: "Peeling quimico"
  },
  {
    id: "juan",
    nombre: "Juan Carlos Perez Gomez",
    dni: "79234118",
    tipo: "Paciente sordomudo",
    area: "Dermatologia",
    motivo: "Exfoliacion"
  }
];

// ==================== CONFIGURACION DEL CALENDARIO ====================
const nombresMes = [
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

const estadosBase = ["Pendiente", "En proceso", "Atendido", "Falto"];
const estadosAgenda = {};
const hoy = new Date();
let fechaActiva = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
let diaSeleccionado = hoy.getDate();

// ==================== GENERACION DE AGENDA POR DIA ====================
function claveAgenda(dia, mes, anio, pacienteId, orden) {
  return `${anio}-${mes + 1}-${dia}-${pacienteId}-${orden}`;
}

function getAgendaDelDia(dia, mes, anio) {
  const total = ((dia + mes + anio) % 4) + 2;
  const inicio = (dia + mes) % pacientes.length;

  return Array.from({ length: total }, (_, index) => {
    const paciente = pacientes[(inicio + index) % pacientes.length];
    const orden = index + 1;
    const clave = claveAgenda(dia, mes, anio, paciente.id, orden);
    const estado = estadosAgenda[clave] || estadosBase[index % estadosBase.length];

    return { orden, paciente, estado, clave };
  });
}

// ==================== METRICAS DEL PANEL ====================
function etiquetaPaciente(tipo) {
  if (tipo === "Paciente sordomudo") {
    return '<span class="etiqueta etiqueta-azul">Senas</span>';
  }
  return '<span class="etiqueta etiqueta-normal">Regular</span>';
}

function actualizarMetricas() {
  const agenda = getAgendaDelDia(hoy.getDate(), hoy.getMonth(), hoy.getFullYear());
  const sordomudos = agenda.filter(({ paciente }) => paciente.tipo === "Paciente sordomudo").length;
  const pendientes = agenda.filter(({ estado }) => estado === "Pendiente" || estado === "En proceso").length;

  document.querySelector("[data-metrica-hoy]").textContent = agenda.length;
  document.querySelector("[data-metrica-pendientes]").textContent = pendientes;
  document.querySelector("[data-metrica-sordomudos]").textContent = sordomudos;
  document.querySelector("[data-metrica-confirmadas]").textContent = agenda.length + 4;
}

// ==================== LISTA DE PACIENTES DEL DIA SELECCIONADO ====================
function renderAgenda(dia) {
  const titulo = document.querySelector("[data-dia-seleccionado]");
  const lista = document.querySelector("[data-lista-pacientes-dia]");
  const mes = fechaActiva.getMonth();
  const anio = fechaActiva.getFullYear();
  const agenda = getAgendaDelDia(Number(dia), mes, anio);

  titulo.textContent = `Dia ${dia} - ${agenda.length} pacientes de Dermatologia`;
  lista.innerHTML = agenda.map(({ orden, paciente, estado, clave }) => `
    <article class="paciente-card" data-clave="${clave}">
      <div class="orden">
        <span>Orden</span>
        <strong>${orden}</strong>
      </div>
      <div>
        <a class="paciente-link" href="../pacientes/detalle-paciente.html?paciente=${paciente.id}">${paciente.nombre}</a>
        <span>DNI: ${paciente.dni}</span>
        <span>Tipo: ${paciente.tipo}</span>
        <span>Area: ${paciente.area}</span>
        <small>Motivo: ${paciente.motivo}</small>
      </div>
      <div class="estado-atencion">
        ${etiquetaPaciente(paciente.tipo)}
        <strong class="estado-badge" data-estado="${estado}">${estado}</strong>
        <div class="estado-controles">
          ${estadosBase.map((item) => `<button type="button" data-clave="${clave}" data-estado-opcion="${item}">${item}</button>`).join("")}
        </div>
      </div>
    </article>
  `).join("");

  lista.querySelectorAll("[data-estado-opcion]").forEach((boton) => {
    boton.addEventListener("click", () => {
      estadosAgenda[boton.dataset.clave] = boton.dataset.estadoOpcion;
      renderAgenda(dia);
      actualizarMetricas();
    });
  });
}

// ==================== CALENDARIO MENSUAL FUNCIONAL ====================
function renderCalendario() {
  const year = fechaActiva.getFullYear();
  const month = fechaActiva.getMonth();
  const totalDias = new Date(year, month + 1, 0).getDate();
  const primerDia = new Date(year, month, 1).getDay();
  const inicioLunes = (primerDia + 6) % 7;
  const contenedor = document.querySelector("[data-calendario-dias]");

  document.querySelector("[data-calendario-titulo]").textContent = `Calendario de ${nombresMes[month]} ${year}`;
  document.querySelector("[data-calendario-periodo]").textContent = `${nombresMes[month]} ${year}`;
  document.querySelector("[data-calendario-total]").textContent = `${totalDias} dias`;
  contenedor.innerHTML = "";

  if (diaSeleccionado > totalDias) diaSeleccionado = totalDias;

  for (let i = 0; i < inicioLunes; i++) {
    const vacio = document.createElement("div");
    vacio.className = "dia-vacio";
    contenedor.appendChild(vacio);
  }

  for (let dia = 1; dia <= totalDias; dia++) {
    const agenda = getAgendaDelDia(dia, month, year);
    const boton = document.createElement("button");
    boton.className = "dia-calendario";
    boton.dataset.dia = String(dia);
    boton.dataset.tipo = agenda.length >= 5 ? "lleno" : agenda.some(({ paciente }) => paciente.tipo === "Paciente sordomudo") ? "senas" : "normal";
    boton.innerHTML = `<strong>${dia}</strong><span>${agenda.length} pacientes</span>`;

    if (dia === diaSeleccionado) boton.classList.add("activo");
    if (year === hoy.getFullYear() && month === hoy.getMonth() && dia === hoy.getDate()) {
      boton.classList.add("hoy");
    }

    boton.addEventListener("click", () => {
      diaSeleccionado = dia;
      document.querySelectorAll(".dia-calendario").forEach((item) => item.classList.remove("activo"));
      boton.classList.add("activo");
      renderAgenda(dia);
    });

    contenedor.appendChild(boton);
  }

  renderAgenda(diaSeleccionado);
}

// ==================== EVENTOS DE NAVEGACION ENTRE MESES ====================
document.querySelector("[data-mes-anterior]").addEventListener("click", () => {
  fechaActiva = new Date(fechaActiva.getFullYear(), fechaActiva.getMonth() - 1, 1);
  diaSeleccionado = 1;
  renderCalendario();
});

document.querySelector("[data-mes-siguiente]").addEventListener("click", () => {
  fechaActiva = new Date(fechaActiva.getFullYear(), fechaActiva.getMonth() + 1, 1);
  diaSeleccionado = 1;
  renderCalendario();
});

// ==================== INICIALIZACION DEL PANEL ====================
actualizarMetricas();
renderCalendario();
