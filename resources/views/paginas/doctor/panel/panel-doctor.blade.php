@extends('layouts.doctor') 

@section('title', 'MediSign - Panel del Doctor')

@push('doctor-styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/doctor/panel/panel-doctor.css') }}">
@endpush

@section('doctor-content')
    <main class="doctor-contenido">
        <header class="cabecera">
            <div>
                <h2>Panel principal del doctor</h2>
                <p>Agenda mensual de {{ $doctor->especialidad_nombre ?? 'Dermatología' }} y pacientes asignados por día.</p>
            </div>
            <div class="doctor-mini">
                @if(!empty($doctor->ruta_imagen))
                    <img src="{{ str_starts_with($doctor->ruta_imagen, 'doctores-perfiles/') ? asset('storage/' . $doctor->ruta_imagen) : asset($doctor->ruta_imagen) }}" alt="{{ $doctor->nombre }}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #00bfa6;" />
                @else
                    <img src="{{ asset('imagenes/doctores/doctor4.webp') }}" alt="Doctor por defecto" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;" />
                @endif
                <div>
                    <strong>{{ $doctor->nombre ?? 'Doctor Desconocido' }}</strong>
                    <span>{{ $doctor->especialidad_nombre ?? 'Dermatología' }}</span>
                </div>
            </div>
        </header>

        <section class="metricas">
            <article class="metrica">
                <span>Pacientes hoy</span>
                <strong data-metrica-hoy>0</strong>
                <small>turno activo</small>
            </article>
            <article class="metrica">
                <span>Pendientes</span>
                <strong data-metrica-pendientes>0</strong>
                <small>por atender</small>
            </article>
            <article class="metrica">
                <span>Pacientes sordomudos</span>
                <strong data-metrica-sordomudos>0</strong>
                <small>usar apoyo visual</small>
            </article>
            <article class="metrica">
                <span>Citas confirmadas</span>
                <strong data-metrica-confirmadas>0</strong>
                <small>agenda estable</small>
            </article>
        </section>

        <section class="panel-agenda">
            <article class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h3 data-calendario-titulo>Calendario</h3>
                        <p>Seleccione un día para ver los pacientes asignados.</p>
                    </div>
                    <span class="etiqueta" data-calendario-total>Mes activo</span>
                </div>

                <div class="calendario-mes">
                    <div class="calendario-controles">
                        <button class="boton boton-secundario" type="button" data-mes-anterior>Anterior</button>
                        <strong data-calendario-periodo>{{ now()->translatedFormat('F Y') }}</strong>
                        <button class="boton boton-secundario" type="button" data-mes-siguiente>Siguiente</button>
                    </div>
                    <div class="calendario-nombres">
                        <span>Lun</span><span>Mar</span><span>Mie</span><span>Jue</span><span>Vie</span><span>Sab</span><span>Dom</span>
                    </div>
                    <div class="calendario-dias" data-calendario-dias></div>
                </div>
            </article>

            <aside class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h3 data-dia-seleccionado>Día {{ now()->format('d') }} - Pacientes asignados</h3>
                        <p>Click en un paciente para abrir su historial y anotaciones.</p>
                    </div>
                </div>
                <div class="lista-pacientes" data-lista-pacientes-dia>
                    </div>
            </aside>
        </section>
    </main>
@endsection

@push('doctor-scripts')
    <script src="{{ asset('js/paginas/doctor/panel/panel-doctor.js') }}"></script>
@endpush