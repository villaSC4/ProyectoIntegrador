@extends('layouts.doctor')

@section('title', 'MediSign - Traductor de Señas')

@push('doctor-styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/doctor/traductor/traductor-senas.css') }}">
@endpush

@section('doctor-content')
    <main class="doctor-contenido">
        <header class="cabecera">
            <div>
                <h2>Traductor de Se&ntilde;as</h2>
                <p>Comunicacion bidireccional para consulta dermatologica.</p>
            </div>
            <div class="doctor-mini">
                <img src="{{ asset('imagenes/header-footer/perfil.webp') }}" alt="Paciente" />
                <div>
                    <strong>Paciente en consulta</strong>
                    <span>Paciente sordomudo</span>
                </div>
            </div>
        </header>

        <section class="estado-consulta">
            <article>
                <span>Entrada del paciente</span>
                <strong>Se&ntilde;as a texto y voz</strong>
            </article>
            <article>
                <span>Salida del doctor</span>
                <strong>Texto a avatar de se&ntilde;as</strong>
            </article>
            <article>
                <span>Modo</span>
                <strong>Tiempo real activo</strong>
            </article>
        </section>

        <section class="traductor-grid">
            <article class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h3>Paciente: se&ntilde;as a texto</h3>
                        <p>La camara enfoca al paciente y el sistema muestra el texto reconocido.</p>
                    </div>
                    <span class="etiqueta" style="background: #10b981; color: white;">En Vivo</span>
                </div>
                
                <div id="mediasign-camera-root">
                    <div class="camara-simulada">
                        <div class="camara-estado" data-estado-camara>Cargando MediaPipe Landmarker...</div>
                    </div>
                </div>

                <div class="traduccion">
                    <div class="traduccion-titulo">
                        <h4>Texto reconocido</h4>
                        <button type="button" class="boton-secundario" data-reproducir-voz>Reproducir voz</button>
                    </div>
                    <p id="texto-traduccion-dinamica" data-traduccion style="font-weight: bold; color: #10b981;">Esperando detección de señas...</p>
                </div>
            </article>

            <aside class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h3>Doctor: texto a se&ntilde;as</h3>
                        <p>El doctor escribe o dicta, y el paciente ve el mensaje con avatar en tiempo real.</p>
                    </div>
                </div>
                <form class="mensaje-doctor" data-form-doctor>
                    @csrf
                    <label for="mensajeDoctor">Mensaje para el paciente</label>
                    <div class="frases frases-doctor">
                        <button type="button" data-frase-doctor="Hola, mucho gusto. Cuenteme como se siente hoy.">Saludo</button>
                        <button type="button" data-frase-doctor="Por favor levante el rostro para revisar la zona afectada.">Levantar rostro</button>
                        <button type="button" data-frase-doctor="Voy a revisar su piel con cuidado. Si siente molestia, aviseme.">Revision</button>
                        <button type="button" data-frase-doctor="Le explicare el tratamiento paso a paso.">Tratamiento</button>
                    </div>
                    <textarea id="mensajeDoctor" data-mensaje-doctor required>Por favor levante el rostro para revisar la zona afectada.</textarea>
                    <div class="acciones-doctor">
                        <button type="button" class="boton-secundario" data-pantalla-paciente>Abrir pantalla del paciente</button>
                        <button type="button" class="boton-secundario" data-microfono data-activo="false">Activar microfono</button>
                        <button type="submit" class="boton-principal">Enviar al paciente</button>
                    </div>
                </form>
                <div class="panel-flujo">
                    <h4>Ultimo mensaje enviado</h4>
                    <p data-ultimo-mensaje>Esperando indicacion del doctor.</p>
                </div>
            </aside>
        </section>
    </main>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endsection

@push('doctor-scripts')
    @viteReactRefresh
    @vite(['resources/js/paginas/doctor/traductor/traductor-senas.jsx'])
@endpush