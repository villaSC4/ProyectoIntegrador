@extends('layouts.doctor')

@section('title', 'MediSign - Historial del Paciente')

@push('doctor-styles')
    <link rel="stylesheet" href="{{ asset('css/paginas/doctor/pacientes/detalle-paciente.css') }}">
@endpush

@section('doctor-content')
    <main class="doctor-contenido" data-cita-id="{{ $citaActual->id }}">
        <header class="cabecera">
            <div>
                <h2>Paciente: {{ $paciente->nombre }}</h2>
                <p>DNI: {{ $paciente->dni }} | Celular: {{ $paciente->celular }}</p>
            </div>
            <a class="boton boton-secundario" href="{{ route('doctor.panel') }}">Volver al panel</a>
        </header>

        <section class="perfil-paciente-grid">
            <aside class="tarjeta perfil-paciente">
                <img src="{{ asset('imagenes/header-footer/perfil.webp') }}" alt="Paciente" />
                <h3>{{ $paciente->nombre }}</h3>
                <p>{{ $paciente->es_sordomudo ? 'Paciente sordomuda' : 'Paciente regular' }}</p>
                <span class="etiqueta etiqueta-azul">Atencion personalizada</span>
                
                <div class="datos">
                    <div><span>DNI</span><strong>{{ $paciente->dni }}</strong></div>
                    <div><span>Edad</span><strong>{{ $edadCalculada }}</strong></div>
                    <div class="dato-fila-bmi" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                        <span>BMI</span>
                        
                        <div class="bmi-interactivo-contenedor" style="width: 70%; display: flex; justify-content: flex-end; text-align: right;">
                            
                            <div id="bmi-visual-bloque" style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                <strong id="texto-bmi" style="color: #173436; font-weight: 600;">{{ $historial->bmi ?? '-------' }}</strong>
                                <button type="button" class="btn-lapiz" id="btn-editar-bmi" title="Editar BMI" style="background:none; border:none; cursor:pointer; padding:0; font-size:12px; line-height: 1;">✏️</button>
                            </div>
                            
                            <form id="form-editar-bmi" class="mini-form-inline oculto" style="display: flex; align-items: center; justify-content: flex-end; gap: 6px; width: 100%; margin: 0;">
                                <input type="text" id="input-bmi" value="{{ $historial->bmi ?? '' }}" placeholder="23.4" required 
                                    style="width: 55px; padding: 3px 6px; border: 1px solid #b9cccc; border-radius: 4px; font-size: 12px; font-weight: 600; color: #173436; text-align: right;" />
                                <div class="mini-form-acciones" style="display: flex; gap: 4px; align-items: center;">
                                    <button type="button" id="btn-guardar-bmi" style="background:none; border:none; cursor:pointer; font-size:12px; padding:0 2px; line-height: 1;">✔️</button>
                                    <button type="button" id="btn-cancelar-bmi" style="background:none; border:none; cursor:pointer; font-size:12px; padding:0 2px; line-height: 1;">❌</button>
                                </div>
                            </form>

                        </div>
                    </div>
                    <div><span>Especialidad</span><strong>{{ $doctorActivo->especialidad_nombre ?? 'Medicina General' }}</strong></div>
                    
                    <div class="dato-fila-motivo" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                        <span>Motivo</span>
                        
                        <div class="motivo-interactivo-contenedor" style="width: 70%; text-align: right;">
                            <div id="motivo-visual-bloque" style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                <strong id="texto-motivo" style="color: #173436; font-weight: 600;">{{ $citaActual->motivo_consulta ?? 'Reserva web' }}</strong>
                                <button type="button" class="btn-lapiz" id="btn-editar-motivo" title="Editar motivo" style="background:none; border:none; cursor:pointer; padding:0; font-size:12px;">✏️</button>
                            </div>
                            
                            <form id="form-editar-motivo" class="mini-form-inline oculto" style="display: flex; align-items: center; justify-content: flex-end; gap: 4px; width: 100%;">
                                <input type="text" id="input-motivo" value="{{ $citaActual->motivo_consulta ?? 'Reserva web' }}" required 
                                    style="width: 75%; padding: 3px 6px; border: 1px solid #b9cccc; border-radius: 4px; font-size: 12px; font-weight: 600; color: #173436; text-align: right;" />
                                <div class="mini-form-acciones" style="display: flex; gap: 2px;">
                                    <button type="button" id="btn-guardar-motivo" style="background:none; border:none; cursor:pointer; font-size:12px; padding:0 2px;">✔️</button>
                                    <button type="button" id="btn-cancelar-motivo" style="background:none; border:none; cursor:pointer; font-size:12px; padding:0 2px;">❌</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="secciones-paciente">
                <article class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h3>Diagnostico y motivo de consulta</h3>
                                <button type="button" class="btn-lapiz" id="btn-editar-diagnostico" title="Editar diagnóstico">✏️</button>
                            </div>
                            <p>Resumen clínico visible para el doctor.</p>
                        </div>
                    </div>
                    
                    <div class="diagnostico-bloque-contenedor" style="padding: 10px 0;">
                        <p class="diagnostico" id="texto-diagnostico">
                            {{ $citaActual->diagnostico ?? 'Reserva web automática. Pendiente de evaluación clínica.' }}
                        </p>
                        <form id="form-editar-diagnostico" class="oculto" style="margin-top: 10px;">
                            <textarea id="textarea-diagnostico" rows="3" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #b9cccc; font-family: inherit;">{{ $citaActual->diagnostico }}</textarea>
                            <div style="display: flex; gap: 8px; margin-top: 8px; justify-content: flex-end;">
                                <button type="button" class="boton boton-secundario" id="btn-cancelar-diagnostico" style="padding: 4px 12px; font-size: 13px;">Cancelar</button>
                                <button type="button" class="boton boton-primario" id="btn-guardar-diagnostico" style="padding: 4px 12px; font-size: 13px;">Guardar</button>
                            </div>
                        </form>
                    </div>
                </article>

                <article class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <div>
                            <h3>Historial Clínico de Consultas</h3>
                            <p>Registros y eventos de atenciones anteriores.</p>
                        </div>
                    </div>
                    <div class="historial">
                        @if($historicoCitas->isEmpty())
                            <p style="color: #6b7280; padding: 10px 0;">Este paciente no registra consultas previas finalizadas.</p>
                        @else
                            @foreach($historicoCitas as $cita)
                                <div class="historial-item" style="border-bottom: 1px solid #f3f4f6; padding: 10px 0;">
                                    <small style="color: #9ca3af;">{{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} - {{ $cita->hora_cita }}</small>
                                    <h4 style="margin: 4px 0; color: #1f2937;">{{ $cita->titulo }}</h4>
                                    <p style="font-size: 13px; color: #4b5563;"><strong>Diagnóstico:</strong> {{ $cita->diagnostico ?? 'Sin diagnóstico registrado' }}</p>
                                    <p style="font-size: 12px; color: #6b7280;"><strong>Tratamiento:</strong> {{ $cita->tratamiento ?? 'N/A' }}</p>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </article>
            </section>

            <article class="tarjeta anotaciones-medicas">
                <div class="tarjeta-cabecera anotaciones-cabecera">
                    <div>
                        <h3>Anotaciones médicas</h3>
                        <p>Archivos clínicos con vista previa y acciones.</p>
                    </div>
                </div>

                <div class="explorador-anotaciones" style="margin-top: 15px;">
                    <div class="archivos-panel">
                        <div class="archivos-lista vista-filas" data-archivos-lista>
                            @foreach($todasLasCitas as $cita)
                                <div class="archivo-item {{ $cita->id == $citaActual->id ? 'activo' : '' }}" 
                                     data-id="{{ $cita->id }}"
                                     data-titulo="{{ $cita->titulo }}"
                                     data-fecha="{{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}"
                                     data-estado="{{ $cita->estado }}"
                                     data-sintomas="{{ $cita->sintomas ?? 'No registrados aún.' }}"
                                     data-diagnostico="{{ $cita->diagnostico ?? 'Pendiente de evaluación.' }}"
                                     data-tratamiento="{{ $cita->tratamiento ?? 'Pendiente de asignación.' }}"
                                     data-observaciones="{{ $cita->observaciones_adicionales ?? 'Sin observaciones.' }}">
                                    <div class="archivo-icono">DOC</div>
                                    <div class="archivo-contenido">
                                        <h4>{{ $cita->titulo }}</h4>
                                        <small>Creado: {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}</small>
                                        <small>Modificado: {{ \Carbon\Carbon::parse($cita->actualizado_en ?? $cita->fecha_cita)->format('d/m/Y') }}</small>
                                        <div class="archivo-estado">{{ $cita->estado }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <aside class="preview-panel" data-preview-panel>
                        <span class="etiqueta etiqueta-azul">Preview</span>
                        <h4 data-preview-titulo>{{ $citaActual->titulo }}</h4>
                        <p data-preview-meta>{{ \Carbon\Carbon::parse($citaActual->fecha_cita)->format('d/m/Y') }} · Estado: {{ $citaActual->estado }}</p>
                        
                        <div class="preview-detalle">
                            <strong>Diagnóstico resumido</strong>
                            <p data-preview-diagnostico>{{ $citaActual->diagnostico ?? 'Pendiente de evaluación.' }}</p>
                        </div>
                        <div class="preview-detalle">
                            <strong>Descripción (Síntomas)</strong>
                            <p data-preview-descripcion>{{ $citaActual->sintomas ?? 'No registrados aún.' }}</p>
                        </div>
                        <div class="preview-detalle">
                            <strong>Doctor responsable</strong>
                            <p>{{ $doctorActivo->nombre ?? 'Dr. Carlos Mendoza Ruiz' }}</p>
                        </div>
                        
                        <div class="preview-acciones">
                            <button class="boton boton-secundario" type="button" data-ver-anotacion>Ver</button>
                            <button class="boton boton-secundario" type="button" data-imprimir-anotacion>Imprimir</button>
                            <button class="boton boton-secundario" type="button" data-editar-anotacion>Editar</button>
                        </div>
                    </aside>
                </div>
            </article>

            <article class="tarjeta recetas-medicas">
                <div class="tarjeta-cabecera anotaciones-cabecera">
                    <div>
                        <h3>Recetas medicas</h3>
                        <p>Medicamentos recetados para este paciente.</p>
                    </div>
                    <button class="boton boton-primario" type="button" data-nueva-receta>+ Nueva Receta</button>
                </div>

                <div class="recetas-grid" style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-top: 15px;">
                    
                    <div class="recetas-lista" data-recetas-lista style="display: flex; flex-direction: column; gap: 15px;">
                        @if(empty($todasLasCitas) || $todasLasCitas->whereNotNull('id')->count() === 0)
                            <div class="diagnostico" style="color: #6b7280; padding: 15px 0;">Este paciente aun no tiene recetas registradas.</div>
                        @else
                            @foreach($todasLasCitas as $cita)
                                @php
                                    $receta = DB::table('recetas_medicas')->where('cita_medica_id', $cita->id)->first();
                                    $medicamentos = $receta ? DB::table('receta_medicamentos')->where('receta_medica_id', $receta->id)->get() : collect();
                                @endphp

                                @if($receta)
                                    <article class="receta-item {{ ($recetaActual && $receta->id == $recetaActual->id) ? 'activo' : '' }}" 
                                        data-receta-id="{{ $receta->id }}"
                                        data-fecha="{{ \Carbon\Carbon::parse($receta->fecha_emision)->format('d/m/Y') }}"
                                        data-vigencia="{{ $receta->fecha_vigencia ? \Carbon\Carbon::parse($receta->fecha_vigencia)->format('d/m/Y') : 'Sin vigencia' }}"
                                        data-observaciones="{{ $receta->indicaciones_generales ?? 'Sin indicaciones generales.' }}"
                                        data-doctor="{{ $doctorActivo->nombre ?? 'Carlos Mendoza Ruiz' }}"
                                        data-especialidad="{{ $doctorActivo->especialidad_nombre ?? 'Dermatología' }}"
                                        data-diagnostico="{{ $cita->diagnostico ?? 'Consulta General' }}"
                                        data-medicamentos-json="{{ json_encode($medicamentos) }}"
                                        style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; background: #ffffff; cursor: pointer; transition: border-color 0.2s;">
                                        
                                        <div class="receta-item-cabecera" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                            <div>
                                                <h4 style="margin: 0 0 4px; color: #173436; font-size: 15px; font-weight: 600;">RX-000{{ $receta->id }} - {{ $cita->diagnostico ?? 'Consulta Médica' }}</h4>
                                                <small style="color: #9ca3af; display: block;">Emitida: {{ \Carbon\Carbon::parse($receta->fecha_emision)->format('d/m/Y') }}</small>
                                                <small style="color: #9ca3af; display: block;">Vigencia: {{ $receta->fecha_vigencia ? \Carbon\Carbon::parse($receta->fecha_vigencia)->format('d/m/Y') : 'Sin vigencia definida' }}</small>
                                            </div>
                                            <span class="etiqueta" style="background: #eef7f5; color: #173436; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">{{ $medicamentos->count() }} med.</span>
                                        </div>

                                        <table class="receta-tabla-mini" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                                            <thead>
                                                <tr style="border-bottom: 1px solid #e5e7eb; color: #6b7280;">
                                                    <th style="padding: 6px 4px; font-weight: 500;">N</th>
                                                    <th style="padding: 6px 4px; font-weight: 500;">Medicamento</th>
                                                    <th style="padding: 6px 4px; font-weight: 500;">Presentacion</th>
                                                    <th style="padding: 6px 4px; font-weight: 500;">Via</th>
                                                    <th style="padding: 6px 4px; font-weight: 500;">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($medicamentos as $index => $med)
                                                    <tr style="border-bottom: 1px solid #f3f4f6; color: #374151;"
                                                        data-nombre="{{ $med->nombre_medicamento }}"
                                                        data-presentacion="{{ $med->presentacion ?? 'N/A' }}"
                                                        data-via="{{ $med->via_administracion }}"
                                                        data-cantidad="{{ $med->cantidad }}">
                                                        <td style="padding: 6px 4px; color: #9ca3af;">{{ $index + 1 }}</td>
                                                        <td style="padding: 6px 4px; font-weight: 500;">{{ $med->nombre_medicamento }}</td>
                                                        <td style="padding: 6px 4px;">{{ $med->presentacion ?? '-------' }}</td>
                                                        <td style="padding: 6px 4px; color: #6b7280;">{{ $med->via_administracion }}</td>
                                                        <td style="padding: 6px 4px;">{{ $med->cantidad }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </article>
                                @endif
                            @endforeach
                        @endif
                    </div>

                    <aside class="preview-panel receta-preview" data-receta-preview style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; background: #ffffff;">
                        <span class="etiqueta etiqueta-azul" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Receta</span>
                        
                        @if($recetaActual)
                            @php
                                $medicamentosActuales = DB::table('receta_medicamentos')->where('receta_medica_id', $recetaActual->id)->get();
                                $citaAsociada = DB::table('citas_medicas')->where('id', $recetaActual->cita_medica_id)->first();
                            @endphp
                            
                            <h4 data-receta-titulo style="color: #173436; font-size: 18px; font-weight: 700; margin: 10px 0 2px;">RX</h4>
                            <p data-receta-meta style="color: #6b7280; font-size: 13px; margin: 0 0 15px;">----</p>
                            
                            <div class="preview-detalle" style="margin-bottom: 15px;">
                                <strong style="display: block; color: #173436; font-size: 13px; margin-bottom: 4px;">Diagnostico relacionado</strong>
                                <p data-receta-diagnostico style="margin: 0; color: #4b5563; font-size: 14px;">----</p>
                            </div>
                            
                            <div class="preview-detalle" style="margin-bottom: 15px;">
                                <strong style="display: block; color: #173436; font-size: 13px; margin-bottom: 6px;">Medicamentos</strong>
                                <div class="receta-medicamentos-preview" data-receta-medicamentos style="display: flex; flex-direction: column; gap: 8px; color: #374151; font-size: 14px;">
                                    @foreach($medicamentosActuales as $med)
                                        <span><strong style="color: #173436;">· {{ $med->nombre_medicamento }}</strong> - {{ $med->presentacion ?? 'Oral' }} · {{ $med->cantidad }} un.</span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <h4 data-receta-titulo style="color: #173436; font-size: 18px; font-weight: 700; margin: 10px 0 2px;">Seleccione una receta</h4>
                            <p data-receta-meta style="color: #6b7280; font-size: 13px; margin: 0 0 15px;">La vista previa aparecerá aquí.</p>
                            <div class="preview-detalle" style="margin-bottom: 15px;">
                                <strong style="display: block; color: #173436; font-size: 13px; margin-bottom: 4px;">Diagnostico relacionado</strong>
                                <p data-receta-diagnostico style="margin: 0; color: #4b5563;">-</p>
                            </div>
                            <div class="preview-detalle" style="margin-bottom: 15px;">
                                <strong style="display: block; color: #173436; font-size: 13px; margin-bottom: 6px;">Medicamentos</strong>
                                <div class="receta-medicamentos-preview" data-receta-medicamentos style="color: #4b5563;">-</div>
                            </div>
                        @endif

                        <div class="preview-acciones" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px;">
                            <button class="boton boton-secundario" type="button" data-ver-receta>Ver</button>
                            <button class="boton boton-secundario" type="button" data-imprimir-receta>Imprimir</button>
                            <button class="boton boton-secundario" type="button" data-editar-receta>Editar</button>
                            <button class="boton boton-secundario boton-peligro" type="button" data-eliminar-receta style="color: #dc2626;">Eliminar</button>
                        </div>
                    </aside>
                </div>
            </article>
        </section>
    </main>

    <div class="modal oculto" data-modal-anotacion id="modal-evolucion">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <div>
                    <h3 data-modal-titulo>Registrar Evolución Médica</h3>
                    <p>Guarda los síntomas, diagnóstico y tratamiento en la cita médica.</p>
                </div>
                <button class="cerrar-modal" type="button" id="btn-cerrar-modal">×</button>
            </div>

            <form class="form-anotacion" id="form-modal-anotacion">
                @csrf
                <input type="hidden" id="modal-anotacion-id" name="anotacion_id" value="">

                <label>Título<input type="text" id="modal-titulo" name="titulo" required /></label>
                <label>Fecha<input type="date" id="modal-fecha" name="fecha" required /></label>
                <label>Descripción médica (Síntomas)<textarea id="modal-descripcion" name="descripcion" required></textarea></label>
                <label>Diagnóstico<textarea id="modal-diagnostico" name="diagnostico" required></textarea></label>
                <label>Tratamiento recomendado<textarea id="modal-tratamiento" name="tratamiento" required></textarea></label>
                <label>Observaciones adicionales<textarea id="modal-observaciones" name="observaciones"></textarea></label>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <label>
                        Estado
                        <select id="modal-estado" name="estado" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #b9cccc;">
                            <option value="Pendiente">Pendiente</option>
                            <option value="En seguimiento">En seguimiento</option>
                            <option value="Finalizado">Finalizado</option>
                            <option value="Confirmado">Confirmado</option>
                        </select>
                    </label>
                   <label>
                        Doctor responsable
                        <input type="text" id="modal-doctor" name="doctor" readonly 
                            value="{{ $doctorActivo->nombre ?? 'Dr. Carlos Mendoza Ruiz' }}" 
                            style="background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; border: 1px solid #d1d5db; width: 100%;" />
                    </label>
                </div>

                <div class="modal-acciones" style="margin-top: 20px;">
                    <button class="boton boton-secundario" type="button" id="btn-cancelar-modal">Cancelar</button>
                    <button class="boton boton-primario" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal oculto" id="modal-ver-anotacion" data-modal-ver>
        <div class="modal-contenido modal-lectura" style="max-width: 650px;">
            <div class="modal-cabecera" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 20px;">
                <div>
                    <h3 id="ver-titulo" style="color: #173436; font-size: 1.3rem; font-weight: 700; margin: 0;">Control post tratamiento</h3>
                    <p id="ver-meta" style="color: #6b7280; font-size: 0.9rem; margin: 5px 0 0 0;">25/5/2026 · En seguimiento</p>
                </div>
                <button class="cerrar-modal" type="button" id="btn-cerrar-ver" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
            </div>

            <div class="lectura-archivo-cuerpo" style="display: flex; flex-direction: column; gap: 15px;">
                
                <div class="bloque-ver-item" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                    <strong style="display: block; color: #173436; font-size: 0.95rem; margin-bottom: 6px;">Descripción médica</strong>
                    <p id="ver-descripcion" style="margin: 0; color: #374151; font-size: 0.9rem; line-height: 1.5;"></p>
                </div>

                <div class="bloque-ver-item" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                    <strong style="display: block; color: #173436; font-size: 0.95rem; margin-bottom: 6px;">Diagnóstico</strong>
                    <p id="ver-diagnostico" style="margin: 0; color: #374151; font-size: 0.9rem; line-height: 1.5;"></p>
                </div>

                <div class="bloque-ver-item" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                    <strong style="display: block; color: #173436; font-size: 0.95rem; margin-bottom: 6px;">Tratamiento recomendado</strong>
                    <p id="ver-tratamiento" style="margin: 0; color: #374151; font-size: 0.9rem; line-height: 1.5;"></p>
                </div>

                <div class="bloque-ver-item" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                    <strong style="display: block; color: #173436; font-size: 0.95rem; margin-bottom: 6px;">Observaciones adicionales</strong>
                    <p id="ver-observaciones" style="margin: 0; color: #374151; font-size: 0.9rem; line-height: 1.5;"></p>
                </div>

                <div class="bloque-ver-item" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; background-color: #fafafa;">
                    <strong style="display: block; color: #173436; font-size: 0.95rem; margin-bottom: 6px;">Doctor responsable</strong>
                    <p id="ver-doctor" style="margin: 0; color: #374151; font-size: 0.9rem; font-weight: 500;">{{ $doctorActivo->nombre ?? 'Dr. Aaron Palomino' }}</p>
                </div>

            </div>
        </div>
    </div>

    <div class="modal oculto" data-modal-receta>
      <div class="modal-contenido modal-receta">
        <div class="modal-cabecera">
          <div>
            <h3 data-receta-modal-titulo>Nueva Receta</h3>
            <p>Documento de medicamentos para entregar al paciente.</p>
          </div>
          <button class="cerrar-modal" type="button" data-cerrar-receta>x</button>
        </div>

        <form class="form-receta" data-form-receta id="form-modal-receta-real" onsubmit="event.preventDefault();">
          @csrf
            <label>
                Fecha de emision
                <input type="date" name="fecha" required value="{{ date('Y-m-d') }}" />
            </label>
            <label>
                Vigencia <span class="campo-ayuda">Opcional</span>
                <input type="date" name="vigencia" />
            </label>
            <label>
                Especialidad
                <input type="text" name="especialidad" readonly 
                    value="{{ $doctorActivo->especialidad_nombre ?? 'Dermatología' }}" 
                    style="background-color: #f8fafb; color: #64787c; cursor: not-allowed;" />
            </label>
            <label>
                Doctor responsable
                <input type="text" name="doctor" readonly 
                    value="{{ $doctorActivo->nombre ?? 'Carlos Mendoza Ruiz' }}" 
                    style="background-color: #f8fafb; color: #64787c; cursor: not-allowed;" />
            </label>

          <section class="medicamentos-form campo-completo">
            <div class="medicamentos-cabecera">
              <div>
                <h4>Medicamentos</h4>
                <p>Agrega uno o varios medicamentos recetados.</p>
              </div>
              <button class="boton boton-secundario" type="button" data-agregar-medicamento>+ Medicamento</button>
            </div>
            <div class="medicamentos-lista-form" data-medicamentos-form></div>
          </section>

          <label class="campo-completo">
            Indicaciones generales
            <span class="campo-ayuda">Opcional</span>
            <textarea name="observaciones" placeholder="Recomendaciones generales para el paciente."></textarea>
          </label>

          <div class="modal-acciones">
            <button class="boton boton-secundario" type="button" data-cancelar-receta>Cancelar</button>
            <button class="boton boton-primario" type="submit">Guardar Receta</button>
          </div>
        </form>
      </div>
    </div>

    <div class="modal oculto" id="modal-ver-receta-real" data-modal-ver>
        <div class="modal-contenido modal-lectura">
            <div class="modal-cabecera">
                <div>
                    <h3 data-ver-titulo>Archivo médico</h3>
                    <p data-ver-meta>Detalle completo</p>
                </div>
                <button class="cerrar-modal" type="button" id="btn-cerrar-modal-ver">×</button>
            </div>
            <div class="lectura-archivo" data-ver-contenido></div>
        </div>
    </div>
@endsection

@push('doctor-scripts')
    <script src="{{ asset('js/paginas/doctor/pacientes/detalle-paciente.js') }}"></script>
@endpush