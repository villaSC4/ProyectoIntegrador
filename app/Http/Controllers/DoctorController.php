<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{

    public function panel()
    {
        try {
            $doctorId = session('doctor_id'); 

            if (!$doctorId) {
                return redirect()->route('login')->with('error', 'Debes iniciar sesión para acceder al panel.');
            }

            $doctor = DB::table('doctores')
                ->join('especialidades', 'doctores.especialidad_id', '=', 'especialidades.id')
                ->select('doctores.*', 'especialidades.nombre as especialidad_nombre')
                ->where('doctores.id', $doctorId)
                ->first();

            if (!$doctor) {
                $doctor = (object)[
                    'id' => $doctorId,
                    'nombre' => session('usuario_nombre') ?? 'Dr. Aaron Palomino',
                    'ruta_imagen' => 'imagenes/doctores/doctor4.webp',
                    'especialidad_nombre' => 'Dermatología'
                ];
            }

            return view('paginas.doctor.panel.panel-doctor', compact('doctor'));
        } catch (\Exception $e) {
            return "Error al inicializar el panel del médico: " . $e->getMessage();
        }
    }

    public function getCitasMes(Request $request)
    {
        try {
            $doctorId = session('doctor_id'); 
            
            $anio = (int) $request->query('anio', now()->year);
            $mes = (int) $request->query('mes', now()->month);

            $citas = DB::table('citas_medicas')
                ->join('users', 'citas_medicas.user_id', '=', 'users.id')
                ->join('doctores', 'citas_medicas.doctor_id', '=', 'doctores.id')
                ->join('especialidades', 'doctores.especialidad_id', '=', 'especialidades.id')
                ->leftJoin('reconocimientos_faciales', function($join) {
                    $join->on('users.id', '=', 'reconocimientos_faciales.biometria_id')
                         ->where('reconocimientos_faciales.biometria_type', '=', 'App\Models\User');
                })
                ->select(
                    'citas_medicas.id',
                    'citas_medicas.user_id',
                    'citas_medicas.fecha_cita',
                    'citas_medicas.hora_cita',
                    'citas_medicas.estado',
                    'citas_medicas.titulo',
                    'citas_medicas.motivo_consulta',
                    'users.nombre as paciente_nombre', 
                    'users.dni as paciente_dni',
                    'especialidades.nombre as especialidad_nombre',
                    DB::raw('CASE WHEN reconocimientos_faciales.id IS NOT NULL THEN 1 ELSE 0 END as es_sordomudo')
                )
                ->where('citas_medicas.doctor_id', $doctorId)
                ->whereYear('citas_medicas.fecha_cita', $anio)
                ->whereMonth('citas_medicas.fecha_cita', $mes)
                ->orderBy('citas_medicas.hora_cita', 'asc')
                ->get();

            $citas = $citas->map(function($cita) {
                if ($cita->fecha_cita) {
                    $cita->fecha_cita = substr($cita->fecha_cita, 0, 10);
                }
                return $cita;
            });

            $fechaHoyStr = now()->toDateString();
            $citasHoy = $citas->filter(fn($c) => $c->fecha_cita === $fechaHoyStr);

            $metricasHoy = [
                'total'       => $citasHoy->count(),
                'pendientes'  => $citasHoy->whereIn('estado', ['Pendiente', 'En proceso'])->count(),
                'sordomudos'  => $citasHoy->where('es_sordomudo', 1)->count(),
                'confirmadas' => $citasHoy->count()
            ];

            return response()->json([
                'success' => true,
                'citas' => $citas,
                'metricasHoy' => $metricasHoy
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de SQL: ' . $e->getMessage(),
                'citas' => []
            ], 500);
        }
    }

    public function actualizarEstadoCita(Request $request)
    {
        try {
            $citaId = $request->input('id');
            $nuevoEstado = $request->input('estado');

            DB::table('citas_medicas')
                ->where('id', $citaId)
                ->update(['estado' => $nuevoEstado]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function detallePaciente($userId)
    {
        try {
            $doctorId = session('doctor_id'); 

            $doctorActivo = DB::table('doctores')
                ->join('especialidades', 'doctores.especialidad_id', '=', 'especialidades.id')
                ->select('doctores.*', 'especialidades.nombre as especialidad_nombre')
                ->where('doctores.id', $doctorId)
                ->first();

            $paciente = DB::table('users')
                ->leftJoin('reconocimientos_faciales', 'users.id', '=', 'reconocimientos_faciales.biometria_id')
                ->select('users.*', DB::raw('CASE WHEN reconocimientos_faciales.id IS NOT NULL THEN 1 ELSE 0 END as es_sordomudo'))
                ->where('users.id', $userId)
                ->first();

            if (!$paciente) {
                return redirect()->route('doctor.panel')->with('error', 'Paciente no encontrado.');
            }

            $datosSunat = DB::table('sunat')->where('dni', $paciente->dni)->first();
            
            $edadCalculada = '-------';
            if ($datosSunat && !empty($datosSunat->fecha_nacimiento)) {
                $edadCalculada = Carbon::parse($datosSunat->fecha_nacimiento)->age; 
            } else {
                $edadCalculada = '27'; 
            }

            $historial = DB::table('historiales_clinicos')->where('user_id', $userId)->first();

            $citaActual = DB::table('citas_medicas')
                ->where('user_id', $userId)
                ->where('doctor_id', $doctorId)
                ->orderBy('fecha_cita', 'desc')
                ->first();

            if (!$citaActual) {
                $citaActual = (object)[
                    'id' => 0,
                    'titulo' => 'Sin cita activa',
                    'fecha_cita' => now()->toDateString(),
                    'estado' => 'Pendiente',
                    'motivo_consulta' => 'Reserva web automatica',
                    'diagnostico' => 'Reserva web automatica. Pendiente de evaluacion clinica.',
                    'sintomas' => 'No registrados aun.',
                    'tratamiento' => 'Pendiente de asignacion.',
                    'observaciones_adicionales' => 'Sin observaciones.'
                ];
            }

            $historicoCitas = DB::table('citas_medicas')
                ->where('user_id', $userId)
                ->where('estado', 'Atendido')
                ->orderBy('fecha_cita', 'desc')
                ->get();
            
            $todasLasCitas = DB::table('citas_medicas')
                ->where('user_id', $userId)
                ->where('doctor_id', $doctorId) 
                ->orderBy('fecha_cita', 'desc')
                ->get();

            $recetaActual = DB::table('recetas_medicas')->where('cita_medica_id', $citaActual->id)->first();

            if ($recetaActual) {
                $recetaActual->medicamentos = DB::table('receta_medicamentos')
                    ->where('receta_medica_id', $recetaActual->id)
                    ->get();
            } else {
                $recetaActual = null;
            }

            return view('paginas.doctor.pacientes.detalle-paciente', compact('paciente', 'historial', 'citaActual', 'historicoCitas', 'recetaActual', 'edadCalculada', 'todasLasCitas', 'doctorActivo'));

        } catch (\Exception $e) {
            return "Error al procesar los datos de identidad: " . $e->getMessage();
        }
    }

    public function actualizarBmiPaciente(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $nuevoBmi = $request->input('bmi');

            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'ID de paciente no recibido.'], 400);
            }

            $pacienteBiometria = DB::table('reconocimientos_faciales')
                ->where('biometria_id', $userId)
                ->where('biometria_type', 'App\Models\User')
                ->first();

            $tipoPaciente = $pacienteBiometria ? 'Sordomudo' : 'Regular';

            DB::table('historiales_clinicos')
                ->updateOrInsert(
                    ['user_id' => $userId], 
                    [
                        'bmi' => $nuevoBmi,
                        'tipo_paciente' => $tipoPaciente, 
                        'actualizado_en' => now()
                    ]
                );

            return response()->json(['success' => true, 'message' => 'BMI guardado con éxito en historiales_clinicos.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error de SQL: ' . $e->getMessage()], 500);
        }
    }

    public function guardarConsulta(Request $request, $citaId)
    {
        try {
            DB::table('citas_medicas')
                ->where('id', $citaId)
                ->update([
                    'sintomas'                  => $request->input('sintomas'),
                    'diagnostico'               => $request->input('diagnostico'),
                    'tratamiento'               => $request->input('tratamiento'),
                    'observaciones_adicionales' => $request->input('observaciones_adicionales'),
                    'estado'                    => 'Atendido', 
                    'actualizado_en'            => now()
                ]);

            return redirect()->back()->with('success', 'Evolución médica guardada con éxito.');

        } catch (\Exception $e) {
            return "Error al guardar el diagnóstico: " . $e->getMessage();
        }
    }

    public function guardarReceta(Request $request, $citaId)
    {
        $request->validate([
            'fecha' => 'required|date',
            'diagnostico' => 'required|string|max:255',
            'medicamentos' => 'required|array|min:1',
            'medicamentos.*.nombre' => 'required|string|max:255',
            'medicamentos.*.dosis' => 'required|string|max:255',
            'medicamentos.*.frecuencia' => 'required|string|max:255',
            'medicamentos.*.duracion' => 'required|string|max:255',
            'medicamentos.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $recetaId = DB::table('recetas_medicas')->insertGetId([
                'cita_medica_id'         => $citaId,
                'fecha_emision'          => $request->input('fecha'),
                'fecha_vigencia'         => $request->input('vigencia') ?: null,
                'indicaciones_generales' => $request->input('observaciones'),
                'creado_en'              => now(),
                'actualizado_en'         => now()
            ]);

            foreach ($request->input('medicamentos') as $med) {
                DB::table('receta_medicamentos')->insert([
                    'receta_medica_id'         => $recetaId, 
                    'nombre_medicamento'       => $med['nombre'],
                    'presentacion'             => $med['presentacion'] ?: null,
                    'via_administracion'       => $med['via_administracion'] ?: 'Tópica',
                    'dosis'                    => $med['dosis'],
                    'frecuencia'               => $med['frecuencia'],
                    'duracion'                 => $med['duracion'],
                    'cantidad'                 => (int) $med['cantidad'],
                    'indicaciones_especificas' => $med['indicaciones_especificas'] ?: null,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Receta médica y medicamentos emitidos correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar la receta: ' . $e->getMessage());
        }
    }

    public function actualizarMotivoCita(Request $request)
    {
        try {
            DB::table('citas_medicas')
                ->where('id', $request->input('id'))
                ->update(['motivo_consulta' => $request->input('motivo_consulta')]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function actualizarDiagnosticoCita(Request $request)
    {
        try {
            DB::table('citas_medicas')
                ->where('id', $request->input('id'))
                ->update(['diagnostico' => $request->input('diagnostico')]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function guardarAnotacionModal(Request $request)
    {
        $request->validate([
            'titulo'       => 'required|string|max:255',
            'fecha'        => 'required|date',
            'descripcion'  => 'required|string',
            'diagnostico'  => 'required|string',
            'tratamiento'  => 'required|string',
            'estado'       => 'required|string|max:30',
        ]);

        $citaId = $request->input('anotacion_id') ?: $request->input('cita_id');

        try {
            DB::table('citas_medicas')
                ->where('id', $citaId)
                ->update([
                    'titulo'                    => $request->input('titulo'),
                    'fecha_cita'                => $request->input('fecha'),
                    'estado'                    => $request->input('estado'),
                    'sintomas'                  => $request->input('descripcion'),
                    'diagnostico'               => $request->input('diagnostico'),
                    'tratamiento'               => $request->input('tratamiento'),
                    'observaciones_adicionales' => $request->input('observaciones'),
                    'actualizado_en'            => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Historial clínico actualizado correctamente en MySQL.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarRecetaMedica(Request $request)
    {
        $request->validate([
            'fecha_emision' => 'required|date',
            'medicamentos'  => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $recetaId = $request->input('receta_id');
            
            if ($recetaId) {
                DB::table('recetas_medicas')->where('id', $recetaId)->update([
                    'fecha_emision'          => $request->input('fecha_emision'),
                    'fecha_vigencia'         => $request->input('fecha_vigencia') ?: null,
                    'indicaciones_generales' => $request->input('indicaciones_generales') ?: null,
                    'actualizado_en'         => now()
                ]);
                
                DB::table('receta_medicamentos')->where('receta_medica_id', $recetaId)->delete();
            } else {
                $recetaId = DB::table('recetas_medicas')->insertGetId([
                    'cita_medica_id'         => $request->input('cita_id'),
                    'fecha_emision'          => $request->input('fecha_emision'),
                    'fecha_vigencia'         => $request->input('fecha_vigencia') ?: null,
                    'indicaciones_generales' => $request->input('indicaciones_generales') ?: null,
                    'creado_en'              => now(),
                    'actualizado_en'         => now()
                ]);
            }

            foreach ($request->input('medicamentos') as $med) {
                DB::table('receta_medicamentos')->insert([
                    'receta_medica_id'         => $recetaId,  
                    'nombre_medicamento'       => $med['nombre'],
                    'presentacion'             => $med['presentacion'] ?: null,
                    'via_administracion'       => $med['via'] ?: 'Oral',
                    'dosis'                    => $med['dosis'],
                    'frecuencia'               => $med['frecuencia'],
                    'duracion'                 => $med['duracion'],
                    'cantidad'                 => $med['cantidad'],
                    'indicaciones_especificas' => $med['indicaciones'] ?: null,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Receta guardada con éxito.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error en MySQL: ' . $e->getMessage()], 500);
        }
    }


    public function eliminarRecetaMedica($id)
    {
        DB::beginTransaction();
        try {
            DB::table('receta_medicamentos')->where('receta_medica_id', $id)->delete();

            $eliminado = DB::table('recetas_medicas')->where('id', $id)->delete();

            if (!$eliminado) {
                return response()->json(['success' => false, 'message' => 'La receta no existe o ya fue eliminada.'], 404);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Receta eliminada correctamente.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error en MySQL: ' . $e->getMessage()], 500);
        }
    }

}