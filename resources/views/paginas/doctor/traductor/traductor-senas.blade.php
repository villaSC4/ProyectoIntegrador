@extends('layouts.doctor')

@section('title', 'MediSign - Traductor de Señas')

@push('doctor-styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/doctor/traductor/traductor-senas.css') }}?v={{ filemtime(public_path('css/paginas/doctor/traductor/traductor-senas.css')) }}">
@endpush

@section('doctor-content')
    @php($mostrarPuntosManos = true)
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

        <section class="traductor-grid">
            <article class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h3>Paciente: se&ntilde;as a texto</h3>
                        <p>La camara enfoca al paciente y el sistema muestra el texto reconocido.</p>
                    </div>
                    <div class="controles-camara">
                        <span class="etiqueta" data-etiqueta-camara>Preparando</span>
                        <button type="button" class="boton-toggle-camara" data-toggle-camara data-activo="false" disabled>
                            Activar camara
                        </button>
                        <button type="button" class="boton-enfocar-camara" data-enfocar-camara disabled>
                            Enfocar
                        </button>
                    </div>
                </div>
                <div class="traduccion traduccion-principal">
                    <div class="traduccion-titulo">
                        <div>
                            <h4>Transcripcion para el doctor</h4>
                            <small data-contador-transcripcion>0 fragmentos confirmados</small>
                        </div>
                        <div class="acciones-transcripcion">
                            <button type="button" class="boton-secundario" data-deshacer-transcripcion disabled>Deshacer</button>
                            <button type="button" class="boton-secundario" data-limpiar-transcripcion disabled>Limpiar</button>
                            <button type="button" class="boton-secundario" data-voz-auto data-activo="false">Voz auto: no</button>
                            <button type="button" class="boton-secundario" data-pausar-voz disabled>Pausar voz</button>
                            <button type="button" class="boton-secundario" data-reiniciar-voz>Leer desde inicio</button>
                        </div>
                    </div>
                    <small class="estado-voz" data-estado-voz>Voz automatica desactivada.</small>
                    <div class="resultado-en-vivo" data-resultado-en-vivo data-estado="esperando">
                        <span>Resultado del modelo</span>
                        <strong data-coincidencia-en-vivo>Esperando una se&ntilde;a...</strong>
                        <small data-estabilidad-en-vivo>En entrenamiento siempre veras la mejor interpretacion para confirmarla o corregirla.</small>
                    </div>
                    <p class="texto-transcripcion" data-traduccion>Esperando se&ntilde;as del paciente...</p>
                </div>
                <div class="camara-simulada camara-real">
                    <video class="video-senas-doctor" data-video-senas autoplay playsinline muted></video>
                    <canvas
                        class="canvas-senas-doctor"
                        data-canvas-senas
                        data-mostrar-puntos="{{ $mostrarPuntosManos ? 'true' : 'false' }}"
                        aria-hidden="true"
                    ></canvas>
                    <div class="camara-guia">
                        <span>Muestre la mano dentro del recuadro</span>
                    </div>
                    <div class="camara-estado" data-estado-camara>Activando camara...</div>
                </div>
                <div class="contexto-biometrico">
                    <span data-contexto-mano>Mano: esperando</span>
                    <span data-contexto-rostro>Rostro: esperando</span>
                    <span data-contexto-torso>Torso: esperando</span>
                    <span data-contexto-calidad>Captura: esperando</span>
                </div>
                @php($mostrarEntrenamientoSenas = true)  
                @if ($mostrarEntrenamientoSenas)
                <button type="button" class="boton-toggle-entrenamiento" data-toggle-entrenamiento data-activo="false">
                    Activar modo entrenamiento
                </button>
                <div class="entrenamiento-senas oculto" data-panel-entrenamiento>
                    <div>
                        <h4>Entrenar gesto</h4>
                        <p>Grabe una sola se&ntilde;a por muestra. Use una o dos manos, rostro y cuerpo igual que en una conversacion real.</p>
                    </div>
                    <div class="entrenamiento-libre">
                        <label for="fraseEntrenamiento">Frase que representa la se&ntilde;a</label>
                        <div class="entrada-entrenamiento">
                            <input id="fraseEntrenamiento" type="text" data-frase-entrenamiento placeholder="Ejemplo: Hola, buenas tardes, soy Amir">
                            <button type="button" data-iniciar-grabacion>Iniciar grabacion</button>
                        </div>
                        <button type="button" class="boton-guardar-frase" data-guardar-frase-entrenada disabled>Detener, revisar y guardar</button>
                    </div>
                    <div class="panel-validacion-sena oculto" data-panel-validacion-sena>
                        <p>Resultado de entrenamiento: <strong data-prediccion-sena>Esperando sena...</strong></p>
                        <div class="acciones-validacion">
                            <button type="button" class="boton-confirmar" data-confirmar-sena>Si, correcto</button>
                            <button type="button" class="boton-corregir" data-corregir-sena>No, corregir</button>
                            <button type="button" class="boton-ninguno" data-ninguna-sena>Ninguno</button>
                            <button type="button" class="boton-descartar" data-descartar-sena>Descartar</button>
                        </div>
                    </div>
                    <div class="correccion-sena oculto" data-panel-correccion>
                        <label for="fraseCorreccion">Frase correcta</label>
                        <div class="entrada-entrenamiento">
                            <input id="fraseCorreccion" type="text" data-frase-correccion list="frasesAprendidas" placeholder="Escriba o busque la frase correcta">
                            <button type="button" data-guardar-correccion>Guardar correccion</button>
                        </div>
                        <datalist id="frasesAprendidas" data-opciones-frases></datalist>
                    </div>
                    <div class="frases-aprendidas">
                        <h5>Diccionario aprendido (modelo actual)</h5>
                        <div class="acciones-entrenamiento" data-frases-aprendidas></div>
                    </div>
                    <p class="estado-entrenamiento" data-estado-entrenamiento>Esperando una mano visible para guardar ejemplos.</p>
                </div>
                @endif
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
    <script src="{{ asset('vendor/mediapipe/camera_utils/camera_utils.js') }}"></script>
    <script src="{{ asset('vendor/mediapipe/hands/hands.js') }}"></script>
    <script src="{{ asset('vendor/mediapipe/pose/pose.js') }}"></script>
    <script src="{{ asset('vendor/mediapipe/face_mesh/face_mesh.js') }}"></script>
    <script src="{{ asset('js/paginas/doctor/traductor/traductor-senas.js') }}?v={{ filemtime(public_path('js/paginas/doctor/traductor/traductor-senas.js')) }}"></script>
@endpush
