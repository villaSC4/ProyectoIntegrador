@extends('layouts.app')

@section('title', 'Reserva de Citas - MediSign')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/citas/citas.css') }}">
@endpush

@section('content')
    <div class="card video-card">
        <h4>Escogemos el Horario</h4>
        <p class="sub">Selecciona el horario de tu disposición</p>

        <div class="video">
            <iframe class="video-tutorial" src="{{ asset('videos/video4.mp4') }}"
                title="Tutorial de Citas"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>

        <p class="desc">
            Este video explica paso a paso cómo sacar tu cita
        </p>
    </div>

    <div class="card-padre options">
        {{-- Bucle dinámico: Respetamos el diseño de tu clase .doctor --}}
        @forelse($doctores as $doctor)
            <div class="doctor">
                <h4>{{ $doctor->nombre }}</h4>
                
                {{-- Imagen dinámica con fallback a una por defecto --}}
                @if($doctor->ruta_imagen)
                    <img src="{{ asset('storage/' . $doctor->ruta_imagen) }}" alt="{{ $doctor->nombre }}">
                @else
                    <img src="{{ asset('imagenes/doctores/doctor4.webp') }}" alt="Doctor por defecto">
                @endif

                <div class="doctor-info">
                    <p>Horario: {{ $doctor->horario }}</p>
                    <p>Turno {{ ucfirst($doctor->turno) }}</p>
                    <span>Cupos Disponibles: {{ $doctor->cupos_disponibles }}</span>
                </div>
                <button class="btn-seleccionar">Seleccionar</button>
            </div>
        @empty
            <div class="card">
                <p>No hay doctores disponibles en este momento.</p>
            </div>
        @endforelse
    </div>

    <div class="acciones-senas">
        <div class="grupo-accion">
            <button onclick="window.location.href='{{ route('cita.confirmada') }}'" class="boton-accion boton-horario" type="button">
                Agendar Cita Ahora
            </button>
        </div>

        <div class="grupo-accion">
            <button class="boton-accion boton-reintentar" type="button" onclick="window.location.reload();">
                Intente de nuevo
            </button>
        </div>
    </div>
@endsection