document.addEventListener("DOMContentLoaded", function() {
    const items = document.querySelectorAll('.item-especialidad');
    const pillSeleccion = document.getElementById('pill-seleccion');
    const btnHorario = document.getElementById('btn-ir-horario');
    const buscador = document.getElementById('input-buscador');
    
    let idSeleccionado = null;

    items.forEach(item => {
        item.addEventListener('click', function() {
            items.forEach(i => {
                i.style.backgroundColor = "";
                i.style.color = "";
                i.style.fontWeight = "";
            });

            this.style.backgroundColor = "#00bfa6";
            this.style.color = "white";
            this.style.fontWeight = "600";

            idSeleccionado = this.getAttribute('data-id');
            pillSeleccion.innerText = `Seleccionada: ${this.innerText.trim()}`;
            pillSeleccion.style.backgroundColor = "#00bfa6";
            pillSeleccion.style.color = "white";
        });
    });

    if (buscador) {
        buscador.addEventListener('input', function() {
            const termino = this.value.toLowerCase().trim();
            
            items.forEach(item => {
                const texto = item.innerText.toLowerCase();
                if (texto.includes(termino)) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }
            });
        });
    }

    if (btnHorario) {
        btnHorario.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!idSeleccionado) {
                alert("Por favor, seleccione una especialidad de la lista antes de continuar.");
                return;
            }

            window.location.href = `/reservar-cita?especialidad_id=${idSeleccionado}`;
        });
    }
});