import React from 'react';
import { createRoot } from 'react-dom/client';
import ComponenteTraductor from './components/ComponenteTraductor';

const salida = document.getElementById("texto-traduccion-dinamica");
const botonVoz = document.querySelector("[data-reproducir-voz]");
const formDoctor = document.querySelector("[data-form-doctor]");
const mensajeDoctor = document.querySelector("[data-mensaje-doctor]");
const ultimoMensaje = document.querySelector("[data-ultimo-mensaje]");
const botonMicrofono = document.querySelector("[data-microfono]");
const botonPantallaPaciente = document.querySelector("[data-pantalla-paciente]");

let ventanaPaciente = null;
let microfonoActivo = false;
let canalPaciente = "BroadcastChannel" in window ? new BroadcastChannel("medisign-paciente") : null;

window.onSignLanguageInterpreted = (textoFormado) => {
    if (salida) {
        salida.textContent = textoFormado || "Esperando detección de señas...";
    }
};

const leerEnVozAlta = (texto) => {
    if (!texto || !("speechSynthesis" in window)) return;
    window.speechSynthesis.cancel();
    const voz = new SpeechSynthesisUtterance(texto);
    voz.lang = "es-PE";
    voz.rate = 0.95;
    window.speechSynthesis.speak(voz);
};

botonVoz?.addEventListener("click", () => {
    leerEnVozAlta(salida?.textContent.trim() || "");
});

const publicarMensajeDoctor = (mensaje) => {
    if (!mensaje) return;
    const payload = { mensaje, fecha: new Date().toISOString() };
    localStorage.setItem("medisignMensajeDoctor", JSON.stringify(payload));
    canalPaciente?.postMessage(payload);
    if (ultimoMensaje) ultimoMensaje.textContent = mensaje;
};

const abrirPantallaPaciente = () => {
    if (ventanaPaciente && !ventanaPaciente.closed) {
        ventanaPaciente.focus();
        return ventanaPaciente;
    }
    ventanaPaciente = window.open("/pantalla-paciente", "medisignPantallaPaciente", "width=980,height=720");
    return ventanaPaciente;
};

formDoctor?.addEventListener("submit", (e) => {
    e.preventDefault();
    const msg = mensajeDoctor?.value.trim();
    if (msg) {
        abrirPantallaPaciente();
        publicarMensajeDoctor(msg);
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

const contenedor = document.getElementById('mediasign-camera-root');
if (contenedor) {
    const root = createRoot(contenedor);
    root.render(
        <React.StrictMode>
            <ComponenteTraductor />
        </React.StrictMode>
    );
}