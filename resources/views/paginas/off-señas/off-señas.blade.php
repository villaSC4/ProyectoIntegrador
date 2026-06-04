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
            <iframe 
                class="video-tutorial" 
                src="{{ asset('videos/video2.mp4') }}"
                title="Video explicativo de especialidades"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            >
            </iframe>
        </div>

        <p class="desc">
            Este video explica cómo solicitar tu cita usando señas
        </p>
    </div>

    <section class="card-especialidades">
        <div class="buscador-especialidad">
            <input type="text" id="input-buscador" placeholder="Busque su Especialidad" />
            
            <div class="iconos-buscador">
                <div class="icono-svg">
                    <img src="{{ asset('imagenes/off-señas/filter.svg') }}" alt="filtro" />
                </div>
                <div class="icono-svg">
                    <img src="{{ asset('imagenes/off-señas/lupa.svg') }}" alt="buscar" />
                </div>
            </div>
        </div>

        <div class="lista-especialidades">
            <ul id="lista-items">
                @foreach($especialidades as $esp)
                    <li class="item-especialidad" data-id="{{ $esp->id }}" style="padding: 12px 20px; cursor: pointer; border-radius: 10px; margin-bottom: 5px; list-style: none; transition: all 0.2s ease;">
                        {{ $esp->nombre }}
                    </li>
                @endforeach
            </ul>
        </div>

        <button type="button" id="pill-seleccion" class="btn-especialidad" style="width: 100%; transition: all 0.3s ease;">
            Ninguna Especialidad Seleccionada
        </button>
    </section>

    <section class="contenedor-horario" style="margin-top: 35px; display: flex; justify-content: center; width: 100%;">
        
        <button id="btn-ir-horario" class="btn-horario" type="button" style="
            background-color: #003e3e; 
            color: #ffffff; 
            border: none; 
            border-radius: 30px; 
            padding: 14px 40px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 15px; 
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0, 62, 62, 0.35);
            transition: all 0.3s ease;
        ">
            <div class="btn-svg" style="
                background-color: #00aaff; 
                border-radius: 50%; 
                width: 42px; 
                height: 42px; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                box-shadow: inset 0 2px 4px rgba(255,255,255,0.2);
            ">
                <img src="{{ asset('imagenes/off-señas/citas-H.svg') }}" alt="Icono Citas" style="width: 22px; height: 22px;" />
            </div>
            
            <span style="font-family: 'Poppins', sans-serif; font-size: 19px; font-weight: 600; letter-spacing: 0.5px;">
                Escoja Horario
            </span>
        </button>

    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/paginas/off-señas/off-señas.js') }}"></script>
@endpush