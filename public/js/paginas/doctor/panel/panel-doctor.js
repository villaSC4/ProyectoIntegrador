document.addEventListener("DOMContentLoaded", function () {
    const nombresMes = [
        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
    ];
    const estadosBase = ["Pendiente", "En proceso", "Atendido", "Falto"];
    
    const hoy = new Date();
    let fechaActiva = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    let diaSeleccionado = hoy.getDate();
    let citasMesActual = [];

    document.querySelector("[data-mes-anterior]").addEventListener("click", () => {
        fechaActiva.setMonth(fechaActiva.getMonth() - 1);
        diaSeleccionado = 1;
        cargarDatosDelMes();
    });

    document.querySelector("[data-mes-siguiente]").addEventListener("click", () => {
        fechaActiva.setMonth(fechaActiva.getMonth() + 1);
        diaSeleccionado = 1;
        cargarDatosDelMes();
    });

    async function cargarDatosDelMes() {
        const anio = fechaActiva.getFullYear();
        const mes = fechaActiva.getMonth() + 1;

        try {
            const response = await fetch(`/doctor/api/citas-mes?anio=${anio}&mes=${mes}`);
            const data = await response.json();

            if (data && data.success) {
                citasMesActual = data.citas || [];
                
                console.log("=== CITAS RECIBIDAS DESDE LA BD ===");
                console.log(citasMesActual); 
                
                actualizarMetricas(data.metricasHoy);
            } else {
                citasMesActual = [];
                actualizarMetricas({ total: 0, pendientes: 0, sordomudos: 0, confirmadas: 0 });
            }
        } catch (error) {
            console.error("Error en fetch:", error);
            citasMesActual = [];
        }
        
        renderCalendario();
    }

    function actualizarMetricas(metricasHoy) {
        if (!metricasHoy) return;
        document.querySelector("[data-metrica-hoy]").textContent = metricasHoy.total || 0;
        document.querySelector("[data-metrica-pendientes]").textContent = metricasHoy.pendientes || 0;
        document.querySelector("[data-metrica-sordomudos]").textContent = metricasHoy.sordomudos || 0;
        document.querySelector("[data-metrica-confirmadas]").textContent = metricasHoy.confirmadas || 0;
    }

    function etiquetaPaciente(esSordomudo) {
        return esSordomudo 
            ? '<span class="etiqueta etiqueta-azul">Señas</span>' 
            : '<span class="etiqueta etiqueta-normal">Regular</span>';
    }

    function renderAgenda(dia) {
        const titulo = document.querySelector("[data-dia-seleccionado]");
        const lista = document.querySelector("[data-lista-pacientes-dia]");
        if (!lista) return;

        const anioStr = fechaActiva.getFullYear();
        const mesStr = String(fechaActiva.getMonth() + 1).padStart(2, '0');
        const diaStr = String(dia).padStart(2, '0');
        
        const fechaFiltro = `${anioStr}-${mesStr}-${diaStr}`;

        const agenda = citasMesActual.filter(cita => {
            if (!cita.fecha_cita) return false;
            const fechaCitaLimpia = cita.fecha_cita.substring(0, 10);
            return fechaCitaLimpia === fechaFiltro;
        });

        titulo.textContent = `Día ${diaStr} - ${agenda.length} pacientes de Dermatología`;

        if (agenda.length === 0) {
            lista.innerHTML = `
                <div style="padding: 40px; text-align: center; color: #6b7280; font-size: 15px;">
                    No hay pacientes asignados para este día.
                </div>`;
            return;
        }

        lista.innerHTML = agenda.map((cita, index) => `
            <article class="paciente-card" data-id="${cita.id}">
                <div class="orden">
                    <span>Orden</span>
                    <strong>${index + 1}</strong>
                </div>
                <div>
                    <a class="paciente-link" href="/doctor/pacientes/${cita.user_id}" style="font-weight: 600; color: #173436; text-decoration: none;">
                        ${cita.paciente_nombre}
                    </a>
                    <span>DNI: ${cita.paciente_dni || '-------'}</span>
                    <span>Tipo: ${cita.es_sordomudo ? 'Paciente sordomudo' : 'Paciente regular'}</span>
                    <span>Área: ${cita.especialidad_nombre || 'Medicina General'}</span>
                    <small>Motivo: ${cita.motivo_consulta || 'Sin especificar'}</small>
                </div>
                <div class="estado-atencion">
                    ${etiquetaPaciente(cita.es_sordomudo)}
                    <strong class="estado-badge" data-estado="${cita.estado}">${cita.estado}</strong>
                    <div class="estado-controles">
                        ${estadosBase.map((item) => `
                            <button type="button" data-id="${cita.id}" data-estado-opcion="${item}">${item}</button>
                        `).join("")}
                    </div>
                </div>
            </article>
        `).join("");

        lista.querySelectorAll("[data-estado-opcion]").forEach((boton) => {
            boton.addEventListener("click", async () => {
                const citaId = boton.dataset.id;
                const nuevoEstado = boton.dataset.estadoOpcion;

                try {
                    await fetch(`/doctor/api/actualizar-estado-cita`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ id: citaId, estado: nuevoEstado })
                    });
                    
                    cargarDatosDelMes();
                } catch (e) {
                    console.error("Error al actualizar estado:", e);
                }
            });
        });
    }

    function renderCalendario() {
        const year = fechaActiva.getFullYear();
        const month = fechaActiva.getMonth();
        const totalDias = new Date(year, month + 1, 0).getDate();
        const primerDia = new Date(year, month, 1).getDay();
        const inicioLunes = (primerDia + 6) % 7;
        const contenedorReal = document.querySelector("[data-calendario-dias]");

        if (!contenedorReal) return;

        document.querySelector("[data-calendario-titulo]").textContent = `Calendario de ${nombresMes[month]} ${year}`;
        document.querySelector("[data-calendario-periodo]").textContent = `${nombresMes[month]} ${year}`;
        document.querySelector("[data-calendario-total]").textContent = `${totalDias} días`;
        contenedorReal.innerHTML = "";

        if (diaSeleccionado > totalDias) diaSeleccionado = totalDias;

        for (let i = 0; i < inicioLunes; i++) {
            const vacio = document.createElement("div");
            vacio.className = "dia-vacio";
            contenedorReal.appendChild(vacio);
        }

        for (let dia = 1; dia <= totalDias; dia++) {
            const mesStr = String(month + 1).padStart(2, '0');
            const diaStr = String(dia).padStart(2, '0');
            const fechaFiltro = `${year}-${mesStr}-${diaStr}`;

            const citasDelDia = citasMesActual.filter(cita => {
                if (!cita.fecha_cita) return false;
                const fechaCitaLimpia = cita.fecha_cita.substring(0, 10);
                return fechaCitaLimpia === fechaFiltro;
            });
            const tieneSordomudo = citasDelDia.some(cita => cita.es_sordomudo);

            const boton = document.createElement("button");
            boton.className = "dia-calendario";
            boton.type = "button";
            boton.dataset.dia = String(dia);
            
            boton.dataset.tipo = citasDelDia.length >= 5 ? "lleno" : tieneSordomudo ? "senas" : "normal";
            
            boton.innerHTML = `<strong>${dia}</strong><span>${citasDelDia.length} pacientes</span>`;

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

            contenedorReal.appendChild(boton);
        }

        renderAgenda(diaSeleccionado);
    }

    cargarDatosDelMes();
});