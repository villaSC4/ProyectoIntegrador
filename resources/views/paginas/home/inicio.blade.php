@extends('layouts.app')

@section('title', 'MediSign - Inicio')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/home/inicio.css') }}">
@endpush

@section('content')
    <section class="bienvenida">
        <div>
            <span class="etiqueta">Atencion inclusiva</span>
            <h1>Bienvenido a MediSign</h1>
            <p>Gestiona tu cita y elige el modo de comunicacion que mejor se adapte a tu atencion medica.</p>
        </div>
        
        <div class="resumen-paciente">
            <span>Paciente identificado</span>
            @auth
                <strong>{{ Auth::user()->nombre ?? 'Paciente Registrado' }}</strong>
                <small>DNI: {{ Auth::user()->dni }}</small>
            @else
                <strong>Usuario Invitado</strong>
                <small>DNI: --------</small>
            @endauth
        </div>
    </section>

    <section class="contenido-grid">
        
        <article class="tarjeta video-card">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Guia para otorgamiento de cita</h2>
                    <p>Video de apoyo para entender el flujo de agendamiento.</p>
                </div>
            </div>

            <div class="video">
                <iframe
                    class="video-tutorial"
                    src="{{ asset('videos/video4.mp4') }}"
                    title="Video explicativo de citas"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
        </article>

        <aside class="tarjeta ruta-card">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Modo de atencion</h2>
                    <p>Selecciona como deseas continuar.</p>
                </div>
            </div>

            <div class="container-card">
                <button class="card-ob card-senas" type="button" onclick="window.location.href='{{ route('señas') }}'">
                    <img src="{{ asset('imagenes/Inicio/manos.svg') }}" alt="Icono Manos" />
                    <span>Reconocimiento de Señas</span>
                    <small>Usar camara para apoyo visual.</small>
                </button>

                <button class="card-ob card-manual" type="button" onclick="window.location.href='{{ route('especialidad.manual') }}'">
                    <img src="{{ asset('imagenes/Inicio/chat.svg') }}" alt="Icono Chat" />
                    <span>Atencion manual</span>
                    <small>Continuar sin reconocimiento.</small>
                </button>
            </div>
        </aside>
        
    </section>
@endsection