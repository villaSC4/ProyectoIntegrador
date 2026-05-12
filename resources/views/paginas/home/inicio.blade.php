@extends('layouts.app')

@section('title', 'Página Principal - MediSign')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/home/inicio.css') }}">
@endpush

@section('content')

    <div class="card video-card">
        <h4>Bienvenida, Explicación para el Otorgamiento de su Cita</h4>

        <div class="video">
            <iframe class="video-tutorial" src="{{ asset('videos/video1.mp4') }}"
                title="Video de YouTube"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>

        <p class="desc">
            Este video explica paso a paso cómo agendar tu cita.
        </p>
    </div>

    <div class="card-padre options">
        <h2>¿Cómo prefieres interactuar?</h2>

        <div class="container-card">
            <div class="card-ob card-señas"
                onclick="window.location.href='{{ route('señas') }}'">
                <img src="{{ asset('imagenes/Inicio/manos.svg') }}" alt="Icono Manos">
                <p>Reconocimiento de Señas Activado</p>
            </div>

            <div class="card-ob card-manual" 
                onclick="window.location.href='{{ route('especialidad.manual') }}'">
                <img src="{{ asset('imagenes/Inicio/chat.svg') }}" alt="Icono Chat">
                <p>Acceso inmediato sin Reconocimiento de señas</p>
            </div>
        </div>
    </div>
@endsection