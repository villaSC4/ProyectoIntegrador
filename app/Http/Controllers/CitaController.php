<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CitaController extends Controller
{
    public function seleccionManual()
    {
        try {
            $especialidades = DB::table('especialidades')->get();
            
            return view('paginas.off-señas.off-señas', compact('especialidades'));
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cargar las especialidades: ' . $e->getMessage());
        }
    }
    
    public function reserva(Request $request)
    {
        try {
            $especialidadId = $request->query('especialidad_id');

            $query = Doctor::where('esta_activo', true)->with('horarios');

            if ($especialidadId) {
                $query->where('especialidad_id', $especialidadId);
            }

            $doctores = $query->get();

            return view('paginas.citas.citas', compact('doctores'));

        } catch (\Exception $e) {
            return back()->with('error', 'Hubo un inconveniente al cargar los horarios: ' . $e->getMessage());
        }
    }

    public function confirmada(Request $request)
    {
        try {
            $doctorId = $request->query('doctor_id');
            $horarioId = $request->query('horario_id');

            if (!$doctorId || !$horarioId) {
                return redirect()->route('home')->with('error', 'Faltan parámetros obligatorios.');
            }

            $doctor = Doctor::with('horarios')->find($doctorId);
            $horario = $doctor ? $doctor->horarios->firstWhere('id', $horarioId) : null;
            
            $especialidad = $doctor ? DB::table('especialidades')->where('id', $doctor->especialidad_id)->first() : null;

            if (!$doctor || !$horario) {
                return redirect()->route('home')->with('error', 'Médico o horario no encontrado.');
            }

            $userId = Auth::id() ?? 1; 
            $fechaActual = now()->toDateString(); 

            DB::table('citas_medicas')->insert([
                'user_id'           => $userId,
                'doctor_id'         => $doctor->id,
                'titulo'            => 'Cita de ' . ($especialidad->nombre ?? 'Especialidad'),
                'fecha_cita'        => $fechaActual,
                'hora_cita'         => $horario->hora_inicio, 
                'estado'            => 'Pendiente',
                'motivo_consulta'   => 'Reserva web automática', 
                'creado_en'         => now(),
                'actualizado_en'    => now()
            ]);

            if ($doctor->cupos_disponibles > 0) {
                $doctor->decrement('cupos_disponibles');
            }

            return view('paginas.confirmacion-citas.confirmacion', compact('doctor', 'horario', 'especialidad'));

        } catch (\Exception $e) {
            return "Error detallado en la transacción de base de datos: " . $e->getMessage();
        }
    }
}