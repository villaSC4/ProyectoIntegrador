@extends('layouts.app')

@section('title', 'Seleccionar Especialidad - MediSign')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/off-señas/off-señas.css') }}">
@endpush

@section('content')
    <div class="card video-card">
        <h4>¿A qué especialidad desea ir?</h4>
        <p>Seleccionamos especialidad</p>

        <div class="video">
            <iframe class="video-tutorial" src="{{ asset('videos/video 3.mp4') }}"
                title="Video de YouTube"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>

        <p class="desc">
            Este video explica cómo solicitar tu cita usando señas
        </p>
    </div>

    <section class="card-especialidades">
        <div class="buscador-especialidad">
            <input type="text" placeholder="Busque su Especialidad">
            <div class="iconos-buscador">
                <div class="icono-svg">
                    <img src="{{ asset('imagenes/off-señas/filter.svg') }}" alt="filtro">
                </div>
                <div class="icono-svg">
                    <img src="{{ asset('imagenes/off-señas/lupa.svg') }}" alt="buscar">
                </div>
            </div>
        </div>

        <div class="lista-especialidades">
            <ul>
                <li>Dermatología</li>
                <li>Ginecología</li>
                <li>Pediatría</li>
                <li>Cardiología</li>
            </ul>
        </div>

        <button class="btn-especialidad">
            Especialidad Seleccionada
        </button>
    </section>

    <section class="contenedor-horario">
        <button onclick="window.location.href='{{ route('cita.reserva') }}'" class="btn-horario">
            <div class="btn-svg">
                <img src="{{ asset('imagenes/off-señas/citas-H.svg') }}" alt="Icono Citas">
            </div>
            <span>Escoja Horario</span>
        </button>
    </section>
@endsection