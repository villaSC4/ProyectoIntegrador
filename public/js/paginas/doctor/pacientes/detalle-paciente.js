document.addEventListener("DOMContentLoaded", function () {
    console.log("=== MediSign: Cargando Manejo de Botones e Historial ===");

    const contenedorPrincipal = document.querySelector("[data-cita-id]");
    const citaId = contenedorPrincipal ? contenedorPrincipal.dataset.citaId : null;
    let medicamentosTemporales = []; 
    let editandoRecetaId = null;
    const modalEvolucion = document.getElementById("modal-evolucion");
    const btnEditarAnotacion = document.querySelector("[data-editar-anotacion]");
    const btnCerrarX = document.getElementById("btn-cerrar-modal");
    const btnCancelarM = document.getElementById("btn-cancelar-modal");

    function abrirModalEvolucion() {
        if (modalEvolucion) modalEvolucion.classList.remove("oculto");
    }

    function cerrarModalEvolucion() {
        if (modalEvolucion) modalEvolucion.classList.add("oculto");
    }

    if (btnCerrarX) btnCerrarX.addEventListener("click", cerrarModalEvolucion);
    if (btnCancelarM) btnCancelarM.addEventListener("click", cerrarModalEvolucion);

    if (btnEditarAnotacion) {
        btnEditarAnotacion.addEventListener("click", function () {
            const filaActiva = document.querySelector("[data-archivos-lista] .archivo-item.activo");
            if (!filaActiva) {
                alert("Por favor, seleccione una cita de la lista izquierda para editar.");
                return;
            }

            document.getElementById("modal-anotacion-id").value = filaActiva.dataset.id;
            document.getElementById("modal-titulo").value = filaActiva.dataset.titulo || "";
            document.getElementById("modal-descripcion").value = filaActiva.dataset.sintomas || "";
            document.getElementById("modal-diagnostico").value = filaActiva.dataset.diagnostico || "";
            document.getElementById("modal-tratamiento").value = filaActiva.dataset.tratamiento || "";
            document.getElementById("modal-observaciones").value = filaActiva.dataset.observaciones || "";
            document.getElementById("modal-estado").value = filaActiva.dataset.estado || "Pendiente";

            const inputFecha = document.getElementById("modal-fecha");
            if (inputFecha && filaActiva.dataset.fecha) {
                const partes = filaActiva.dataset.fecha.split('/');
                if (partes.length === 3) {
                    inputFecha.value = `${partes[2]}-${partes[1].padStart(2, '0')}-${partes[0].padStart(2, '0')}`;
                }
            }

            abrirModalEvolucion();
        });
    }

    const filasCitas = document.querySelectorAll("[data-archivos-lista] .archivo-item");
    const previewTitulo = document.querySelector("[data-preview-titulo]");
    const previewMeta = document.querySelector("[data-preview-meta]");
    const previewDiag = document.querySelector("[data-preview-diagnostico]");
    const previewDesc = document.querySelector("[data-preview-descripcion]");

    filasCitas.forEach(fila => {
        fila.addEventListener("click", function () {
            filasCitas.forEach(f => f.classList.remove("activo"));
            this.classList.add("activo");

            if (previewTitulo) previewTitulo.textContent = this.dataset.titulo;
            if (previewMeta) previewMeta.textContent = `${this.dataset.fecha} · Estado: ${this.dataset.estado}`;
            if (previewDiag) previewDiag.textContent = this.dataset.diagnostico;
            if (previewDesc) previewDesc.textContent = this.dataset.sintomas;
        });
    });

    const btnEditarMotivo = document.getElementById("btn-editar-motivo");
    const btnCancelarMotivo = document.getElementById("btn-cancelar-motivo");
    const btnGuardarMotivo = document.getElementById("btn-guardar-motivo");
    const visualMotivo = document.getElementById("motivo-visual-bloque");
    const formMotivo = document.getElementById("form-editar-motivo");
    const inputMotivo = document.getElementById("input-motivo");
    const textoMotivo = document.getElementById("texto-motivo");

    if (btnEditarMotivo) {
        btnEditarMotivo.addEventListener("click", () => {
            visualMotivo.classList.add("oculto");
            formMotivo.classList.remove("oculto");
            inputMotivo.focus();
        });
    }
    if (btnCancelarMotivo) {
        btnCancelarMotivo.addEventListener("click", () => {
            formMotivo.classList.add("oculto");
            visualMotivo.classList.remove("oculto");
        });
    }
    if (btnGuardarMotivo) {
        btnGuardarMotivo.addEventListener("click", async (e) => {
            e.preventDefault();
            const nuevoMotivo = inputMotivo.value.trim();
            if (!nuevoMotivo || !citaId) return;

            const response = await fetch(`/doctor/api/actualizar-motivo-cita`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: citaId, motivo_consulta: nuevoMotivo })
            });
            const data = await response.json();
            if (data.success) {
                textoMotivo.textContent = nuevoMotivo;
                formMotivo.classList.add("oculto");
                visualMotivo.classList.remove("oculto");
            }
        });
    }

    const btnEditarDiag = document.getElementById("btn-editar-diagnostico");
    const btnCancelarDiag = document.getElementById("btn-cancelar-diagnostico");
    const btnGuardarDiag = document.getElementById("btn-guardar-diagnostico");
    const textoDiag = document.getElementById("texto-diagnostico");
    const formDiag = document.getElementById("form-editar-diagnostico");
    const textareaDiag = document.getElementById("textarea-diagnostico");

    if (btnEditarDiag) {
        btnEditarDiag.addEventListener("click", () => {
            textoDiag.classList.add("oculto");
            formDiag.classList.remove("oculto");
            textareaDiag.focus();
        });
    }
    if (btnCancelarDiag) {
        btnCancelarDiag.addEventListener("click", () => {
            formDiag.classList.add("oculto");
            textoDiag.classList.remove("oculto");
        });
    }
    if (btnGuardarDiag) {
        btnGuardarDiag.addEventListener("click", async (e) => {
            e.preventDefault();
            const nuevoDiag = textareaDiag.value.trim();
            if (!nuevoDiag || !citaId) return;

            const response = await fetch(`/doctor/api/actualizar-diagnostico-cita`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: citaId, diagnostico: nuevoDiag })
            });
            const data = await response.json();
            if (data.success) {
                textoDiag.textContent = nuevoDiag;
                formDiag.classList.add("oculto");
                textoDiag.classList.remove("oculto");
            }
        });
    }

    const modalReceta = document.querySelector("[data-modal-receta]");
    const btnNuevaReceta = document.querySelector("[data-nueva-receta]");
    const btnCerrarReceta = document.querySelector("[data-cerrar-receta]");
    const btnCancelarReceta = document.querySelector("[data-cancelar-receta]");

    if (btnNuevaReceta && modalReceta) btnNuevaReceta.addEventListener("click", () => modalReceta.classList.remove("oculto"));
    if (btnCerrarReceta && modalReceta) btnCerrarReceta.addEventListener("click", () => modalReceta.classList.add("oculto"));
    if (btnCancelarReceta && modalReceta) btnCancelarReceta.addEventListener("click", () => modalReceta.classList.add("oculto"));

    const formModalAnotacion = document.getElementById("form-modal-anotacion");
    if (formModalAnotacion) {
        formModalAnotacion.addEventListener("submit", async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const idForm = formData.get('anotacion_id') || citaId;
            formData.append('cita_id', idForm);

            const response = await fetch(`/doctor/api/guardar-anotacion-modal`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                cerrarModalEvolucion();
                
                if (previewTitulo) previewTitulo.textContent = formData.get('titulo');
                if (previewDiag) previewDiag.textContent = formData.get('diagnostico');
                if (previewDesc) previewDesc.textContent = formData.get('descripcion');

                const fila = document.querySelector(`[data-archivos-lista] .archivo-item[data-id="${idForm}"]`);
                if (fila) {
                    fila.querySelector("h4").textContent = formData.get('titulo');
                    fila.querySelector(".archivo-estado").textContent = formData.get('estado');
                    fila.dataset.titulo = formData.get('titulo');
                    fila.dataset.diagnostico = formData.get('diagnostico');
                    fila.dataset.sintomas = formData.get('descripcion');
                    fila.dataset.tratamiento = formData.get('tratamiento');
                    fila.dataset.observaciones = formData.get('observaciones');
                    fila.dataset.estado = formData.get('estado');
                }
                alert("Evolución médica actualizada con éxito.");
            }
        });
    }
  const modalVer = document.getElementById("modal-ver-anotacion");
  const btnVerAnotacion = document.querySelector("[data-ver-anotacion]");
  const btnCerrarVer = document.getElementById("btn-cerrar-ver");

  if (btnVerAnotacion && modalVer) {
      btnVerAnotacion.addEventListener("click", function () {
          const filaActiva = document.querySelector("[data-archivos-lista] .archivo-item.activo");
          if (!filaActiva) {
              alert("Por favor, seleccione una anotación médica de la lista izquierda.");
              return;
          }

          document.getElementById("ver-titulo").textContent = filaActiva.dataset.titulo || "Consulta Médica";
          document.getElementById("ver-meta").textContent = `${filaActiva.dataset.fecha} · ${filaActiva.dataset.estado}`;
          document.getElementById("ver-descripcion").textContent = filaActiva.dataset.sintomas || "No registrados.";
          document.getElementById("ver-diagnostico").textContent = filaActiva.dataset.diagnostico || "Pendiente de evaluación.";
          document.getElementById("ver-tratamiento").textContent = filaActiva.dataset.tratamiento || "No asignado.";
          document.getElementById("ver-observaciones").textContent = filaActiva.dataset.observaciones || "Sin observaciones adicionales.";
          
          modalVer.classList.remove("oculto");
      });
  }

  if (btnCerrarVer && modalVer) {
      btnCerrarVer.addEventListener("click", () => modalVer.classList.add("oculto"));
  }

  const btnImprimirAnotacion = document.querySelector("[data-imprimir-anotacion]");

  if (btnImprimirAnotacion) {
      btnImprimirAnotacion.addEventListener("click", function () {
          const filaActiva = document.querySelector("[data-archivos-lista] .archivo-item.activo");
          
          if (!filaActiva) {
              alert("Por favor, seleccione una cita del listado izquierdo para poder imprimir.");
              return;
          }

          const titulo = filaActiva.dataset.titulo || "Consulta Médica";
          const fecha = filaActiva.dataset.fecha || "";
          const estado = filaActiva.dataset.estado || "Pendiente";
          const sintomas = filaActiva.dataset.sintomas || "No registrados.";
          const diagnostico = filaActiva.dataset.diagnostico || "Pendiente de evaluación.";
          const tratamiento = filaActiva.dataset.tratamiento || "No asignado.";
          const observaciones = filaActiva.dataset.observaciones || "Sin observaciones adicionales.";
          const especialidadDoctor = document.querySelector(".perfil-paciente .datos div:nth-child(4) strong")?.textContent || "Medicina General";
          const nombreDoctor = document.querySelector("#ver-doctor")?.textContent || "Dr. Mendoza";
          
          const nombrePaciente = document.querySelector("header.cabecera h2")?.textContent.replace("Paciente: ", "") || "Paciente";

          const ventanaImpresion = window.open("", "impresionAnotacion", "width=850,height=750");
          if (!ventanaImpresion) {
              alert("Por favor, permita las ventanas emergentes (pop-ups) para poder imprimir el documento.");
              return;
          }

          ventanaImpresion.document.write(`
              <!doctype html>
              <html lang="es">
              <head>
                  <meta charset="UTF-8" />
                  <title>MediSign - ${titulo}</title>
                  <style>
                      body { font-family: 'Poppins', Arial, sans-serif; color: #173436; margin: 40px; padding: 0; background: #ffffff; }
                      .header-print { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #173436; padding-bottom: 15px; margin-bottom: 25px; }
                      h1 { font-size: 24px; margin: 0; color: #173436; font-weight: 700; }
                      .meta-print { color: #64787c; font-size: 14px; margin-top: 5px; }
                      .paciente-info { background: #f8fafb; border-radius: 6px; padding: 12px 18px; margin-bottom: 20px; font-size: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border: 1px solid #e5e7eb; }
                      .bloque-clinico { border: 1px solid #d9e8e7; border-radius: 8px; padding: 15px; margin-bottom: 15px; page-break-inside: avoid; }
                      strong { display: block; color: #173436; font-size: 15px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
                      p { margin: 0; line-height: 1.6; color: #374151; font-size: 14px; }
                      .firma-medico { display: flex; justify-content: flex-end; margin-top: 60px; }
                      .firma-contenedor { width: 280px; text-align: center; border-top: 1px solid #173436; padding-top: 8px; font-size: 13px; }
                  </style>
              </head>
              <body>
                  <div class="header-print">
                      <div>
                          <h1>MediSign - Reporte Clínico de Evolución</h1>
                          <div class="meta-print">Fecha de Atención: ${fecha} &nbsp;|&nbsp; Estado: ${estado}</div>
                      </div>
                  </div>

                  <div class="paciente-info">
                      <div><strong>Paciente:</strong> ${nombrePaciente}</div>
                      <div><strong>Especialidad:</strong> Dermatología</div>
                  </div>

                  <div class="bloque-clinico">
                      <strong>Título de la Consulta</strong>
                      <p>${titulo}</p>
                  </div>

                  <div class="bloque-clinico">
                      <strong>Descripción médica (Síntomas)</strong>
                      <p>${sintomas}</p>
                  </div>

                  <div class="bloque-clinico">
                      <strong>Diagnóstico Oficial</strong>
                      <p>${diagnostico}</p>
                  </div>

                  <div class="bloque-clinico">
                      <strong>Tratamiento recomendado</strong>
                      <p>${tratamiento}</p>
                  </div>

                  <div class="bloque-clinico">
                      <strong>Observaciones adicionales</strong>
                      <p>${observaciones}</p>
                  </div>

                  <div class="firma-medico">
                      <div class="firma-contenedor">
                          <strong>${nombreDoctor}</strong><br>
                          Firma y Sello del Especialista<br>
                          C.M.P. ${especialidadDoctor}
                      </div>
                  </div>
              </body>
              </html>
          `);

          ventanaImpresion.document.close();
          ventanaImpresion.focus();
          
          setTimeout(() => {
              ventanaImpresion.print();
          }, 300);
      });
  }

    function renderMedicamentosForm() {
        const contenedor = document.querySelector("[data-medicamentos-form]");
        if (!contenedor) return;

        contenedor.innerHTML = medicamentosTemporales
            .map((medicamento, index) => `
                <div class="medicamento-form-item" data-medicamento-index="${index}" style="border-bottom: 1px dashed #e5e7eb; padding-bottom: 15px; margin-bottom: 15px;">
                    <label>Medicamento<input type="text" data-campo="nombre" value="${medicamento.nombre || ''}" required /></label>
                    <label>Presentacion <span class="campo-ayuda">Opcional</span><input type="text" data-campo="presentacion" value="${medicamento.presentacion || ''}" /></label>
                    <label>Via <span class="campo-ayuda">Opcional</span><input type="text" data-campo="via" value="${medicamento.via || 'Oral'}" /></label>
                    <label>Dosis<input type="text" data-campo="dosis" value="${medicamento.dosis || ''}" required /></label>
                    <label>Frecuencia<input type="text" data-campo="frecuencia" value="${medicamento.frecuencia || ''}" required /></label>
                    <label>Duracion<input type="text" data-campo="duracion" value="${medicamento.duracion || ''}" required /></label>
                    <label>Cantidad<input type="text" data-campo="cantidad" value="${medicamento.cantidad || ''}" required /></label>
                    <label>Indicaciones <span class="campo-ayuda">Opcional</span><input type="text" data-campo="indicaciones" value="${medicamento.indicaciones || ''}" /></label>
                    <button class="boton boton-secundario boton-peligro" type="button" data-quitar-medicamento="${index}" style="margin-top: 10px;">Quitar medicamento</button>
                </div>
            `).join("");

        contenedor.querySelectorAll("input").forEach((input) => {
            input.addEventListener("input", () => {
                const item = input.closest("[data-medicamento-index]");
                if (item) {
                    const idx = Number(item.dataset.medicamentoIndex);
                    medicamentosTemporales[idx][input.dataset.campo] = input.value;
                }
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
    
    function abrirFormularioReceta(receta = null) {
      const form = document.querySelector("[data-form-receta]") || document.getElementById("form-modal-receta-real");
      if (!form) return;

      editandoRecetaId = receta?.id || null;
      
      medicamentosTemporales = receta
          ? receta.medicamentos.map((item) => ({ ...item }))
          : [{ nombre: "", presentacion: "", via: "Oral", dosis: "", frecuencia: "", duracion: "", cantidad: "", indicaciones: "" }];

      document.querySelector("[data-receta-modal-titulo]").textContent = receta ? "Editar Receta" : "Nueva Receta";
      
      form.fecha.value = receta?.fecha || new Date().toISOString().split('T')[0];
      form.vigencia.value = receta?.vigencia || "";
      form.especialidad.value = receta?.especialidad || "Dermatología";
      form.doctor.value = receta?.doctor || "Dr. Carlos Mendoza Ruiz";
      form.diagnostico.value = receta?.diagnostico || "";
      form.observaciones.value = receta?.observaciones || "";

      renderMedicamentosForm();
      document.querySelector("[data-modal-receta]").classList.remove("oculto");
  }

  const btnAgregarMedicamentoNativo = document.querySelector("[data-agregar-medicamento]");
  if (btnAgregarMedicamentoNativo) {
      btnAgregarMedicamentoNativo.addEventListener("click", function() {
          medicamentosTemporales.push({
              nombre: "",
              presentacion: "",
              via: "Oral",
              dosis: "",
              frecuencia: "",
              duracion: "",
              cantidad: "",
              indicaciones: "",
          });
          renderMedicamentosForm();
      });
  }

  async function guardarReceta(event) {
      if (event) {
          event.preventDefault();
          event.stopPropagation();
      }
      
      const contenedorPrincipal = document.querySelector("[data-cita-id]");
      const citaIdActiva = contenedorPrincipal ? contenedorPrincipal.dataset.citaId : null;
      
      const inputFecha = document.querySelector('input[name="fecha"]');
      const inputVigencia = document.querySelector('input[name="vigencia"]');
      const inputEspecialidad = document.querySelector('input[name="especialidad"]');
      const inputDiagnostico = document.querySelector('input[name="diagnostico"]'); 
      const inputObservaciones = document.querySelector('textarea[name="observaciones"]');
      const inputDoctor = document.querySelector('input[name="doctor"]');

      if (medicamentosTemporales.length === 0) {
          alert("Por favor, agregue al menos un medicamento antes de guardar.");
          return;
      }

      const fechaHoyDefault = new Date().toISOString().split('T')[0];

      const payload = {
          receta_id: editandoRecetaId,
          cita_id: citaIdActiva, 
          fecha_emision: (inputFecha && inputFecha.value) ? inputFecha.value : fechaHoyDefault,
          fecha_vigencia: (inputVigencia && inputVigencia.value) ? inputVigencia.value : null,
          especialidad: (inputEspecialidad && inputEspecialidad.value) ? inputEspecialidad.value : 'Dermatología',
          diagnostico_breve: (inputDiagnostico && inputDiagnostico.value) ? inputDiagnostico.value : '',
          indicaciones_generales: (inputObservaciones && inputObservaciones.value) ? inputObservaciones.value : '',
          doctor: (inputDoctor && inputDoctor.value) ? inputDoctor.value : 'Dr. Carlos Mendoza Ruiz',
          medicamentos: medicamentosTemporales 
      };

      console.log("=== Enviando Datos a Laravel ===", payload);

      try {
          const response = await fetch('/doctor/api/guardar-receta-medica', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                  'Accept': 'application/json'
              },
              body: JSON.stringify(payload)
          });

          const data = await response.json();
          if (data.success) {
              alert("¡Receta médica guardada con éxito en la base de datos MySQL!");
              const modal = document.querySelector("[data-modal-receta]");
              if (modal) modal.classList.add("oculto");
              window.location.reload(); 
          } else {
              alert("Error en el servidor: " + data.message);
          }
      } catch (error) {
          console.error("Error crítico en fetch de recetas:", error);
          alert("Error de comunicación: No se pudo conectar con el servidor Laravel.");
      }
  }

  const formularioRecetaReal = document.getElementById("form-modal-receta-real");
    
  if (formularioRecetaReal) {
      formularioRecetaReal.removeEventListener("submit", guardarReceta);
      
      formularioRecetaReal.addEventListener("submit", guardarReceta);
      console.log("=== MediSign: Evento submit de recetas conectado con éxito ===");
  }

    const tarjetasRecetas = document.querySelectorAll("[data-recetas-lista] .receta-item");

    tarjetasRecetas.forEach((tarjeta) => {
        tarjeta.addEventListener("click", function () {
            tarjetasRecetas.forEach((t) => {
                t.classList.remove("activo");
                t.style.borderColor = "#e5e7eb";
                t.style.background = "#ffffff";     
            });
            
            this.classList.add("activo");
            this.style.borderColor = "#173436";  
            this.style.background = "#f0f7f6";     

            const recetaId = this.dataset.recetaId;
            const fecha = this.dataset.fecha;
            const diagnostico = this.dataset.diagnostico || "Consulta Médica";
            const observaciones = this.dataset.observaciones;
            const especialidad = this.dataset.especialidad || "Dermatología";
            const medicamentos = JSON.parse(this.dataset.medicamentosJson || "[]");

            const previewTitulo = document.querySelector("[data-receta-preview] [data-receta-titulo]");
            const previewMeta = document.querySelector("[data-receta-preview] [data-receta-meta]");
            const previewDiag = document.querySelector("[data-receta-preview] [data-receta-diagnostico]");
            const previewMedsContenedor = document.querySelector("[data-receta-preview] [data-receta-medicamentos]");

            if (previewTitulo) previewTitulo.textContent = `RX-000${recetaId}`;
            if (previewMeta) previewMeta.textContent = `${fecha} · ${especialidad}`;
            if (previewDiag) previewDiag.textContent = observaciones; 

            if (previewMedsContenedor) {
                previewMedsContenedor.innerHTML = medicamentos.length > 0
                    ? medicamentos.map(m => `<span><strong style="color: #173436;">· ${m.nombre_medicamento}</strong> - ${m.presentacion ?? 'Oral'} · ${m.cantidad} un.</span>`).join("")
                    : '<span style="color: #6b7280;">No hay medicamentos.</span>';
            }
        });
    });

    const btnVerRecetaReal = document.querySelector("[data-ver-receta]");
    if (btnVerRecetaReal) {
        btnVerRecetaReal.addEventListener("click", function () {
            const recetaActiva = document.querySelector("[data-recetas-lista] .receta-item.activo");
            if (!recetaActiva) {
                alert("Por favor, seleccione una receta médica del listado izquierdo para poder ver el detalle.");
                return;
            }

            const recetaId = recetaActiva.dataset.recetaId;
            const fecha = recetaActiva.dataset.fecha;
            const vigencia = recetaActiva.dataset.vigencia;
            const diagnostico = recetaActiva.dataset.diagnostico || "Consulta Médica";
            const observaciones = recetaActiva.dataset.observaciones;
            const doctor = recetaActiva.dataset.doctor;
            const medicamentos = JSON.parse(recetaActiva.dataset.medicamentosJson || "[]");

            let tablaHtml = `
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
                    <thead>
                        <tr style="background: #eef7f5; color: #173436;">
                            <th style="border: 1px solid #b9cccc; padding: 8px;">N</th>
                            <th style="border: 1px solid #b9cccc; padding: 8px;">Medicamento</th>
                            <th style="border: 1px solid #b9cccc; padding: 8px;">Presentación</th>
                            <th style="border: 1px solid #b9cccc; padding: 8px;">Vía</th>
                            <th style="border: 1px solid #b9cccc; padding: 8px;">Dosis</th>
                            <th style="border: 1px solid #b9cccc; padding: 8px;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            medicamentos.forEach((m, idx) => {
                tablaHtml += `
                    <tr style="color: #374151;">
                        <td style="border: 1px solid #b9cccc; padding: 8px; text-align: center;">${idx + 1}</td>
                        <td style="border: 1px solid #b9cccc; padding: 8px;"><strong>${m.nombre_medicamento}</strong></td>
                        <td style="border: 1px solid #b9cccc; padding: 8px;">${m.presentacion ?? '-------'}</td>
                        <td style="border: 1px solid #b9cccc; padding: 8px;">${m.via_administracion}</td>
                        <td style="border: 1px solid #b9cccc; padding: 8px;">${m.dosis}</td>
                        <td style="border: 1px solid #b9cccc; padding: 8px; text-align: center;">${m.cantidad}</td>
                    </tr>
                `;
            });
            tablaHtml += `</tbody></table>`;

            const contenedorLectura = document.querySelector("[data-ver-contenido]");
            const modalVerReal = document.getElementById("modal-ver-receta-real") || document.querySelector("[data-modal-ver]");
            
            if (contenedorLectura && modalVerReal) {
                document.querySelector("[data-modal-ver] [data-ver-titulo]").textContent = `Receta Médica N° ${recetaId}`;
                document.querySelector("[data-modal-ver] [data-ver-meta]").textContent = `Emitido: ${fecha} · Vence: ${vigencia}`;
                
                contenedorLectura.innerHTML = `
                    <article style="margin-bottom: 12px;"><strong>Diagnóstico Relacionado</strong><p>${diagnostico}</p></article>
                    <article style="margin-bottom: 12px;"><strong>Medicamentos Recetados</strong>${tablaHtml}</article>
                    <article style="margin-bottom: 12px;"><strong>Indicaciones Generales</strong><p>${observaciones}</p></article>
                    <article style="margin-bottom: 12px; background: #f8fafb; padding: 10px; border-radius: 6px;"><strong>Médico Emisor</strong><p>${doctor}</p></article>
                `;

                modalVerReal.classList.remove("oculto");
            }
        });
    }

    const btnCerrarModalVerReal = document.getElementById("btn-cerrar-modal-ver");
    if (btnCerrarModalVerReal) {
        btnCerrarModalVerReal.addEventListener("click", function() {
            const modalVerReal = document.getElementById("modal-ver-receta-real") || document.querySelector("[data-modal-ver]");
            if (modalVerReal) modalVerReal.classList.add("oculto");
        });
    }

    const btnEditarRecetaReal = document.querySelector("[data-editar-receta]");

    if (btnEditarRecetaReal) {
        btnEditarRecetaReal.addEventListener("click", function () {
            const recetaActiva = document.querySelector("[data-recetas-lista] .receta-item.activo");
            if (!recetaActiva) {
                alert("Por favor, seleccione una receta médica del listado izquierdo para poder editarla.");
                return;
            }

            editandoRecetaId = recetaActiva.dataset.recetaId; 
            const fecha = recetaActiva.dataset.fecha;
            const vigencia = recetaActiva.dataset.vigencia;
            const observaciones = recetaActiva.dataset.observaciones;
            const diagnostico = recetaActiva.dataset.diagnostico || "Consulta Médica";
            const medicamentos = JSON.parse(recetaActiva.dataset.medicamentosJson || "[]");

            const form = document.getElementById("form-modal-receta-real") || document.querySelector("[data-form-receta]");
            if (form) {
                if (fecha) {
                    const partesF = fecha.split('/');
                    if (partesF.length === 3) form.fecha.value = `${partesF[2]}-${partesF[1].padStart(2, '0')}-${partesF[0].padStart(2, '0')}`;
                }
                
                if (vigencia && vigencia !== 'Sin vigencia') {
                    const partesV = vigencia.split('/');
                    if (partesV.length === 3) form.vigencia.value = `${partesV[2]}-${partesV[1].padStart(2, '0')}-${partesV[0].padStart(2, '0')}`;
                } else {
                    form.vigencia.value = "";
                }

                if (form.observaciones) form.observaciones.value = observaciones === 'Sin indicaciones generales.' ? '' : observaciones;
                if (form.diagnostico) form.diagnostico.value = diagnostico;

                medicamentosTemporales = medicamentos.map(m => ({
                    nombre: m.nombre_medicamento,
                    presentacion: m.presentacion || "",
                    via: m.via_administracion || "Oral",
                    dosis: m.dosis || "",
                    frecuencia: m.frecuencia || "",
                    duracion: m.duracion || "",
                    cantidad: m.cantidad || "",
                    indicaciones: m.indicaciones_especificas || ""
                }));

                const tituloModal = document.querySelector("[data-receta-modal-titulo]");
                if (tituloModal) tituloModal.textContent = `Editar Receta N° ${editandoRecetaId}`;

                renderMedicamentosForm();
                const modalReceta = document.querySelector("[data-modal-receta]");
                if (modalReceta) modalReceta.classList.remove("oculto");
            }
        });
    }


    const btnImprimirRecetaReal = document.querySelector("[data-imprimir-receta]");

    if (btnImprimirRecetaReal) {
        btnImprimirRecetaReal.addEventListener("click", function () {
            const recetaActiva = document.querySelector("[data-recetas-lista] .receta-item.activo");
            if (!recetaActiva) {
                alert("Por favor, seleccione una receta médica del listado izquierdo para poder imprimir.");
                return;
            }

            const recetaId = recetaActiva.dataset.recetaId;
            const fecha = recetaActiva.dataset.fecha;
            const vigencia = recetaActiva.dataset.vigencia;
            const diagnostico = recetaActiva.dataset.diagnostico || "Consulta Médica";
            const observaciones = recetaActiva.dataset.observaciones;
            const doctor = recetaActiva.dataset.doctor;
            const especialidad = recetaActiva.dataset.especialidad;
            const medicamentos = JSON.parse(recetaActiva.dataset.medicamentosJson || "[]");

            const nombrePaciente = document.querySelector("header.cabecera h2")?.textContent.replace("Paciente: ", "") || "Paciente Registrado";
            const dniPaciente = document.querySelector("header.cabecera p")?.textContent.split("|")[0]?.replace("DNI: ", "")?.trim() || "-------";

            let filasMedicamentosHtml = "";
            medicamentos.forEach((item, index) => {
                filasMedicamentosHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${item.nombre_medicamento}</strong></td>
                        <td>${item.presentacion ?? '-------'}</td>
                        <td>${item.via_administracion || 'Oral'}</td>
                        <td>${item.dosis || 'No especificada'}</td>
                        <td>${item.frecuencia}</td>
                        <td>${item.duracion}</td>
                        <td>${item.cantidad} un.</td>
                    </tr>
                `;
            });

            const ventanaImpresion = window.open("", "impresionReceta", "width=980,height=720");
            if (!ventanaImpresion) {
                alert("Por favor, permita las ventanas emergentes (pop-ups) para poder imprimir la receta médica.");
                return;
            }

            ventanaImpresion.document.write(`
                <!doctype html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8" />
                    <title>MediSign - Receta N° RX-000${recetaId}</title>
                    <style>
                        body { font-family: 'Poppins', Arial, sans-serif; color: #173436; margin: 28px; font-size: 12px; }
                        h1 { margin: 0 0 8px; font-size: 22px; color: #173436; }
                        .cabecera { display: flex; justify-content: space-between; gap: 18px; border-bottom: 2px solid #173436; padding-bottom: 10px; margin-bottom: 12px; }
                        .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 18px; margin-bottom: 12px; }
                        .bloque { border: 1px solid #d9e8e7; border-radius: 6px; padding: 10px; margin-bottom: 10px; page-break-inside: avoid; }
                        strong { color: #173436; }
                        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                        th, td { border: 1px solid #b9cccc; padding: 6px; text-align: left; vertical-align: top; }
                        th { background: #eef7f5; color: #173436; font-weight: 600; }
                        p { margin: 4px 0; line-height: 1.45; color: #374151; }
                        .firma { display: flex; justify-content: flex-end; margin-top: 42px; page-break-inside: avoid; }
                        .firma div { width: 260px; text-align: center; border-top: 1px solid #173436; padding-top: 8px; font-weight: 500; }
                    </style>
                </head>
                <body>
                    <div class="cabecera">
                        <div>
                            <h1>MediSign - Receta Médica</h1>
                            <p><strong>Receta N°:</strong> RX-000${recetaId}</p>
                        </div>
                        <div style="text-align: right;">
                            <p><strong>Fecha de Emisión:</strong> ${fecha}</p>
                            <p><strong>Vigencia:</strong> ${vigencia}</p>
                        </div>
                    </div>
                    
                    <section class="datos">
                        <p><strong>Paciente:</strong> ${nombrePaciente}</p>
                        <p><strong>DNI:</strong> ${dniPaciente}</p>
                        <p><strong>Médico Tratante:</strong> ${doctor}</p>
                        <p><strong>Especialidad:</strong> ${especialidad}</p>
                    </section>
                    
                    <section class="bloque">
                        <strong>Diagnóstico Relacionado:</strong>
                        <p>${diagnostico}</p>
                    </section>
                    
                    <section class="bloque">
                        <strong>Medicamentos Recetados:</strong>
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Medicamento</th>
                                    <th>Presentación</th>
                                    <th>Vía</th>
                                    <th>Dosis</th>
                                    <th>Frecuencia</th>
                                    <th>Duración</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${filasMedicamentosHtml}
                            </tbody>
                        </table>
                    </section>
                    
                    <section class="bloque">
                        <strong>Indicaciones Generales y Observaciones:</strong>
                        <p>${observaciones}</p>
                    </section>
                    
                    <section class="firma">
                        <div>
                            Firma y Sello del Médico<br />
                            <strong>${doctor}</strong><br />
                            C.M.P. ${especialidad}
                        </div>
                    </section>
                </body>
                </html>
            `);

            ventanaImpresion.document.close();
            ventanaImpresion.focus();

            setTimeout(() => {
                ventanaImpresion.print();
            }, 250);
        });
    }


    const btnEliminarRecetaReal = document.querySelector("[data-eliminar-receta]");

    if (btnEliminarRecetaReal) {
        btnEliminarRecetaReal.addEventListener("click", async function () {
            const recetaActiva = document.querySelector("[data-recetas-lista] .receta-item.activo");
            if (!recetaActiva) {
                alert("Por favor, seleccione una receta médica del listado izquierdo para poder eliminarla.");
                return;
            }

            const recetaId = recetaActiva.dataset.recetaId;

            if (!confirm(`¿Está seguro de eliminar la receta médica N° RX-000${recetaId}? Esta acción borrará también todos sus medicamentos asociados.`)) {
                return;
            }

            try {
                const response = await fetch(`/doctor/api/eliminar-receta-medica/${recetaId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert("¡Receta médica y sus medicamentos eliminados con éxito de MySQL!");
                    window.location.reload();
                } else {
                    alert("Error en el servidor: " + data.message);
                }

            } catch (error) {
                console.error("Error crítico en fetch al eliminar receta:", error);
                alert("Error de comunicación: No se pudo conectar con el servidor Laravel para procesar la eliminación.");
            }
        });
    }

    const btnEditarBmi = document.getElementById("btn-editar-bmi");
const btnCancelarBmi = document.getElementById("btn-cancelar-bmi");
const btnGuardarBmi = document.getElementById("btn-guardar-bmi");
const bloqueVisualBmi = document.getElementById("bmi-visual-bloque");
const formEditarBmi = document.getElementById("form-editar-bmi");
const inputBmi = document.getElementById("input-bmi");
const textoBmi = document.getElementById("texto-bmi");

if (btnEditarBmi) {
    btnEditarBmi.addEventListener("click", () => {
        bloqueVisualBmi.classList.add("oculto");
        formEditarBmi.classList.remove("oculto");
        inputBmi.focus();
    });
}

if (btnCancelarBmi) {
    btnCancelarBmi.addEventListener("click", () => {
        formEditarBmi.classList.add("oculto");
        bloqueVisualBmi.classList.remove("oculto");
        inputBmi.value = textoBmi.textContent.trim() === "-------" ? "" : textoBmi.textContent.trim();
    });
}

if (btnGuardarBmi) {
    btnGuardarBmi.addEventListener("click", async () => {
        const nuevoBmi = inputBmi.value.trim();
        
        // Extrae el ID del paciente de forma segura desde la URL (/doctor/pacientes/XX)
        const match = window.location.pathname.match(/\/pacientes\/(\d+)/);
        const userId = match ? match[1] : null;

        if (!nuevoBmi) {
            alert("Por favor, ingrese un valor de BMI valido.");
            return;
        }

        if (!userId) {
            alert("No se pudo recuperar el ID del paciente.");
            return;
        }

        try {
            const response = await fetch(`/doctor/api/actualizar-bmi-paciente`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ user_id: userId, bmi: nuevoBmi })
            });
            const data = await response.json();

            if (data.success) {
                textoBmi.textContent = nuevoBmi;
                formEditarBmi.classList.add("oculto");
                bloqueVisualBmi.classList.remove("oculto");
            } else {
                alert("Error en base de datos: " + data.message);
            }
        } catch (error) {
            console.error("Error en la peticion asincrona de BMI:", error);
        }
    });
}
}); 