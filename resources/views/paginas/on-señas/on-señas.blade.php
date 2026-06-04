@extends('layouts.app')

@section('title', 'MediSign - Reconocimiento de Señas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/home/inicio.css') }}">
    <link rel="stylesheet" href="{{ asset('css/paginas/on-señas/on-señas.css') }}">
@endpush

@section('content')
    <section class="seccion-senas">
        
        <div class="encabezado-senas">
            <h1>¿A qué especialidad desea ir?</h1>
            <p>Seleccione su especialidad mediante reconocimiento de señas</p>
        </div>

        <div class="card video-card">
            <h4>Presiona el botón y detecta tus señas</h4>
            
            <div class="video">
                <iframe 
                    class="video-tutorial" 
                    src="{{ asset('videos/video4.mp4') }}"
                    title="Video explicativo de señas" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
            
            <p class="desc">Este video explica cómo solicitar tu cita usando señas</p>
        </div>

        <div class="card reconocimiento-card">
            <div class="texto-reconocimiento">
                <h3>Reconocimiento de Señas Activado</h3>
                <p>La cámara está lista para reconocer tus señas y ayudarte a seleccionar una especialidad.</p>
            </div>
            
            <div class="camara-reconocimiento" id="contenedor-ia" style="position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                
                <img id="placeholder-mano" src="{{ asset('imagenes/Inicio/manos.svg') }}" alt="Reconocimiento de señas" />
                
                <p>Puedes usar señas para navegar o solicitar información de citas médicas.</p>
                
                <span id="texto-estado" style="display: none;">Cargando cámara...</span>
                
                <div id="resultado-sena" class="pill-detectando" style="margin-top: 10px; padding: 8px 20px; background-color: #5372e7; color: white; border-radius: 20px; font-weight: 600;">
                    Detectando señas...
                </div>
            </div>
        </div>

        <div class="acciones-senas">
            <div class="grupo-accion">
                <p>Si la especialidad es correcta</p>
                
                <button 
                    onclick="window.location.href = '{{ route('cita.reserva') }}'"
                    class="boton-accion boton-horario" 
                    type="button"
                >
                    Escoja Horario
                </button>
            </div>

            <div class="grupo-accion">
                <p>Si la especialidad no es la correcta</p>
                
                <button id="btn-reintentar" class="boton-accion boton-reintentar" type="button">
                    Intente de nuevo
                </button>
            </div>
        </div>
        
    </section>

    @push('scripts')
        <script src="{{ asset('js/paginas/on-señas/on-señas.js') }}"></script>
    @endpush
@endsection