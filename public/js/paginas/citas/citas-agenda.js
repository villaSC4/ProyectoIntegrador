document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal-agenda");
    const cerrarModal = document.getElementById("cerrar-modal");
    const modalImg = document.getElementById("modal-doctor-img");
    const modalNombre = document.getElementById("modal-doctor-nombre");
    const contenedorDias = document.getElementById("contenedor-dias-disponibles");
    const btnConfirmar = document.getElementById("btn-confirmar-cita");

    let doctorSeleccionadoId = null;
    let horarioSeleccionadoId = null;

    document.querySelectorAll(".card-doctor-clicable").forEach(tarjeta => {
        tarjeta.addEventListener("click", function () {
            doctorSeleccionadoId = this.getAttribute("data-id");
            const nombre = this.getAttribute("data-nombre");
            const imagen = this.getAttribute("data-imagen");
            
            const horarios = JSON.parse(this.getAttribute("data-horarios"));

            modalImg.src = imagen;
            modalNombre.innerText = nombre;

            contenedorDias.innerHTML = "";
            btnConfirmar.style.display = "none";
            horarioSeleccionadoId = null;

            if (horarios && horarios.length > 0) {
                horarios.forEach(horario => {
                    const hInicio = formatTime(horario.hora_inicio);
                    const hFin = formatTime(horario.hora_fin);

                    const botonDia = document.createElement("button");
                    botonDia.type = "button";
                    botonDia.className = "opcion-horario-modal";
                    
                    botonDia.innerHTML = `<strong>${horario.dia_semana}</strong> — Turno ${horario.turno} <span style="font-size: 13px; color: #6b7280; margin-left: auto;">(${hInicio} - ${hFin})</span>`;
                    
                    Object.assign(botonDia.style, {
                        width: "100%",
                        padding: "14px 20px",
                        backgroundColor: "#f9fafb", 
                        color: "#1f2937",          
                        border: "1px solid #e5e7eb", 
                        borderRadius: "16px",        
                        cursor: "pointer",
                        textAlign: "left",
                        display: "flex",             
                        alignItems: "center",
                        fontSize: "14px",
                        fontWeight: "500",
                        boxShadow: "0 2px 4px rgba(0,0,0,0.02)",
                        transition: "all 0.25s ease"
                    });

                    botonDia.addEventListener("click", function (e) {
                        e.stopPropagation(); 
                        
                        document.querySelectorAll(".opcion-horario-modal").forEach(btn => {
                            btn.style.backgroundColor = "#f9fafb";
                            btn.style.color = "#1f2937";
                            btn.style.borderColor = "#e5e7eb";
                            const span = btn.querySelector('span');
                            if(span) span.style.color = "#6b7280";
                        });

                        botonDia.style.backgroundColor = "#00bfa6";
                        botonDia.style.borderColor = "#00bfa6";
                        botonDia.style.color = "white"; 
                        const spanActivo = botonDia.querySelector('span');
                        if(spanActivo) spanActivo.style.color = "#e0f2fe"; 

                        horarioSeleccionadoId = horario.id;
                        btnConfirmar.style.display = "block";
                    });

                    contenedorDias.appendChild(botonDia);
                });
            } else {
                contenedorDias.innerHTML = "<p style='color: #4b5563;'>Este doctor no cuenta con turnos configurados esta semana.</p>";
            }

            const cuerpoModal = modal.querySelector('div');
            if (cuerpoModal) {
                cuerpoModal.style.backgroundColor = "#ffffff"; 
                cuerpoModal.style.color = "#111827";           
                cuerpoModal.style.boxShadow = "0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)";
                
                const subTituloModal = cuerpoModal.querySelector('p');
                if (subTituloModal) subTituloModal.style.color = "#4b5563";
            }

            modal.style.display = "flex";
        });
    });

    cerrarModal.addEventListener("click", () => {
        modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });

    if (btnConfirmar) {
        btnConfirmar.addEventListener("click", function (e) {
            e.preventDefault(); 
            e.stopPropagation();

            if (!doctorSeleccionadoId || !horarioSeleccionadoId) {
                alert("Por favor, selecciona un horario antes de agendar.");
                return;
            }

            btnConfirmar.innerText = "Registrando cita...";
            btnConfirmar.style.backgroundColor = "#6b7280";
            btnConfirmar.disabled = true;

            window.location.href = "/cita-confirmada?doctor_id=" + doctorSeleccionadoId + "&horario_id=" + horarioSeleccionadoId;
        });
    }

    function formatTime(timeString) {
        if (!timeString) return "";
        const [horas, minutos] = timeString.split(":");
        let hour = parseInt(horas, 10);
        const ampm = hour >= 12 ? "pm" : "am";
        hour = hour % 12;
        hour = hour ? hour : 12;
        return `${hour}:${minutos}${ampm}`;
    }
});