@extends('layouts.app')

@section('title', 'Reserva de Citas - MediSign')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/citas/citas.css') }}">
@endpush

@section('content')
    <div class="card video-card">
        <h4>Escogemos el Médico</h4>
        <p class="sub">Selecciona al profesional de tu preferencia para ver su disponibilidad</p>

        <div class="video">
            <iframe 
                class="video-tutorial" 
                src="{{ asset('videos/video1.mp4') }}"
                title="Tutorial de Citas"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        </div>
        <p class="desc">Este video explica paso a paso cómo seleccionar a tu médico y su horario</p>
    </div>

    <div class="card-padre options">
        @forelse($doctores as $doctor)
            <div class="doctor card-doctor-clicable" data-id="{{ $doctor->id }}" data-nombre="{{ $doctor->nombre }}" data-imagen="{{ $doctor->ruta_imagen ? asset($doctor->ruta_imagen) : asset('imagenes/doctores/doctor4.webp') }}" data-horarios="{{ json_encode($doctor->horarios) }}" style="cursor: pointer;">
                
                <h4>{{ $doctor->nombre }}</h4>
                
                @if($doctor->ruta_imagen)
                    <img src="{{ asset($doctor->ruta_imagen) }}" alt="{{ $doctor->nombre }}">
                @else
                    <img src="{{ asset('imagenes/doctores/doctor4.webp') }}" alt="Doctor por defecto">
                @endif

                <div class="doctor-info">
                    <span style="background-color: #00bfa6; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                        Ver horarios disponibles
                    </span>
                    <p style="margin-top: 8px;">Cupos: {{ $doctor->cupos_disponibles ?? 4 }}</p>
                </div>
                
                <button type="button" class="btn-seleccionar">Ver Agenda</button>
            </div>
        @empty
            <div class="card" style="width: 100%; text-align: center; padding: 2rem;">
                <p>No hay doctores disponibles para esta especialidad en este momento.</p>
            </div>
        @endforelse
    </div>

    <div id="modal-agenda" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); align-items: center; justify-content: center;">
        <div style="background-color: #1f2937; border: 2px solid #00bfa6; padding: 30px; border-radius: 25px; width: 90%; max-width: 500px; text-align: center; color: white; box-shadow: 0 10px 25px rgba(0,0,0,0.5); position: relative;">
            
            <button id="cerrar-modal" style="position: absolute; top: 15px; right: 20px; background: none; border: none; color: #ff4a5a; font-size: 24px; cursor: pointer; font-weight: bold;">&times;</button>
            
            <h3 style="color: #00bfa6; font-size: 22px; margin-bottom: 5px;">Elige el día de atención</h3>
            <p style="color: #9ca3af; font-size: 14px; margin-bottom: 20px;">Selecciona el turno que mejor se adapte a tu disposición</p>
            
            <img id="modal-doctor-img" src="" alt="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #00bfa6; margin-bottom: 10px;">
            <h4 id="modal-doctor-nombre" style="font-size: 18px; margin-bottom: 20px;"></h4>
            
            <div id="contenedor-dias-disponibles" style="display: flex; flex-direction: column; gap: 12px; max-height: 200px; overflow-y: auto; padding: 5px;">
                </div>
            
            <button id="btn-confirmar-cita" class="boton-accion boton-horario" style="margin-top: 25px; width: 100%; padding: 12px; background-color: #00bfa6; color: white; border: none; border-radius: 15px; font-weight: bold; cursor: pointer; display: none;">
                Agendar Cita Ahora
            </button>
        </div>
    </div>

    <div class="acciones-senas" style="margin-top: 40px;">
        <div class="grupo-accion">
            <button class="boton-accion boton-reintentar" type="button" onclick="window.history.back();">
                Regresar a Señas
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/paginas/citas/citas-agenda.js') }}"></script>
@endpush