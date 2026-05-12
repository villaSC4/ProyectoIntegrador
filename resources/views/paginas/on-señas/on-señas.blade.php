@extends('layouts.app')

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
                <iframe class="video-tutorial" src="{{ asset('videos/video2.mp4') }}"
                    title="Video de YouTube" allowfullscreen>
                </iframe>
            </div>
            <p class="desc">Este video explica cómo solicitar tu cita usando señas</p>
        </div>

        <div class="card reconocimiento-card">
            <div class="texto-reconocimiento">
                <h3>Reconocimiento de Señas Activado</h3>
                <p>La cámara está lista para reconocer tus señas.</p>
            </div>
            <div class="camara-reconocimiento">
                <img src="{{ asset('imagenes/Inicio/manos.svg') }}" alt="Icono">
                <p>Navega o solicita información mediante señas.</p>
                <span>Detectando señas...</span>
            </div>
        </div>

        <div class="acciones-senas">
            <div class="grupo-accion">
                <p>Si la especialidad es correcta</p>
                <button class="boton-accion boton-horario" onclick="window.location.href='{{ route('cita.reserva') }}'">Escoja Horario</button>
            </div>
            <div class="grupo-accion">
                <p>Si la especialidad no es correcta</p>
                <button class="boton-accion boton-reintentar">Intente de nuevo</button>
            </div>
        </div>
    </section>
@endsection