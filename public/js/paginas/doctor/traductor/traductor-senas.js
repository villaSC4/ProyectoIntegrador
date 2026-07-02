import React from 'react';
import { createRoot } from 'react-dom/client';
import ComponenteTraductor from './components/ComponenteTraductor';

// ==================== ELEMENTOS DEL DOM DEL TRADUCTOR ====================
const salida = document.getElementById("texto-traduccion-dinamica");
const botonVoz = document.querySelector("[data-reproducir-voz]");
const formDoctor = document.querySelector("[data-form-doctor]");
const mensajeDoctor = document.querySelector("[data-mensaje-doctor]");
const ultimoMensaje = document.querySelector("[data-ultimo-mensaje]");
const botonMicrofono = document.querySelector("[data-microfono]");
const botonPantallaPaciente = document.querySelector("[data-pantalla-paciente]");

// ==================== ESTADO DE LA PANTALLA EXTERNA Y MICROFONO ====================
let ventanaPaciente = null;
let microfonoActivo = false;
let canalPaciente = null;

if ("BroadcastChannel" in window) {
  canalPaciente = new BroadcastChannel("medisign-paciente");
}

// ==================== FUNCION GLOBAL: CONEXIÓN CON LA IA DE REACT ====================
// El componente de React llamará a esta función cada vez que se forme una nueva palabra/frase
window.onSignLanguageInterpreted = (textoFormado) => {
  if (salida) {
    salida.textContent = textoFormado || "Esperando detección de señas...";
  }
};

// ==================== VOZ: TEXTO RECONOCIDO HACIA AUDIO ====================
const leerEnVozAlta = (texto) => {
  if (!texto || !("speechSynthesis" in window)) return;

  window.speechSynthesis.cancel();
  const voz = new SpeechSynthesisUtterance(texto);
  voz.lang = "es-PE";
  voz.rate = 0.95;
  window.speechSynthesis.speak(voz);
};

// ==================== COMUNICACION CON PANTALLA EXTERNA DEL PACIENTE ====================
const publicarMensajeDoctor = (mensaje) => {
  if (!mensaje) return;

  const payload = {
    mensaje,
    fecha: new Date().toISOString()
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
    "width=980,height=720,resizable=yes,scrollbars=yes"
  );

  return ventanaPaciente;
};

const enviarAPantallaPaciente = () => {
  const mensaje = mensajeDoctor?.value.trim();
  if (!mensaje) return;

  abrirPantallaPaciente();
  publicarMensajeDoctor(mensaje);
};

// ==================== EVENTOS DEL PACIENTE: REPRODUCIR VOZ ====================
botonVoz?.addEventListener("click", () => {
  leerEnVozAlta(salida?.textContent.trim() || "");
});

// ==================== EVENTOS DEL DOCTOR: PANTALLA, TEXTO, FRASES Y MICROFONO ====================
botonPantallaPaciente?.addEventListener("click", () => {
  abrirPantallaPaciente();
  publicarMensajeDoctor(mensajeDoctor?.value.trim() || "Pantalla del paciente lista para recibir indicaciones.");
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
  botonMicrofono.textContent = microfonoActivo ? "Desactivar microfono" : "Activar microfono";

  if (microfonoActivo) {
    abrirPantallaPaciente();
    if (mensajeDoctor) {
      mensajeDoctor.value = "Doctor hablando: necesito revisar la zona afectada y explicarle el tratamiento.";
      publicarMensajeDoctor(mensajeDoctor.value.trim());
    }
    return;
  }

  publicarMensajeDoctor("Microfono desactivado. El doctor detuvo la comunication por voz.");
});

formDoctor?.addEventListener("submit", (event) => {
  event.preventDefault();
  enviarAPantallaPaciente();
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