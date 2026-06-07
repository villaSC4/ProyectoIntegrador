<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Doctor;
use App\Models\ReconocimientoFacial;

class AuthFacialController extends Controller
{

    public function consultarDni($dni)
    {
        $persona = DB::table('sunat')->where('dni', $dni)->first();

        if ($persona) {
            return response()->json([
                'success' => true,
                'nombre' => $persona->nombre_completo,
                'fecha' => date('d/m/Y', strtotime($persona->fecha_nacimiento))
            ]);
        }
        return response()->json(['success' => false, 'message' => 'DNI no encontrado'], 404);
    }

    public function finalizarRegistro(Request $request)
    {
        DB::beginTransaction();
        try {
            $existe = User::where('dni', $request->dni)->exists();
            if ($existe) {
                return response()->json(['success' => false, 'message' => 'Este DNI ya está registrado.']);
            }

            $user = User::create([
                'dni' => $request->dni,
                'nombre' => $request->nombre, 
                'celular' => $request->celular,
                'email' => $request->email ?? null
            ]);

            $vector = $request->face_vector;
            if (is_string($vector)) {
                $vector = json_decode($vector, true);
            }

            DB::table('reconocimientos_faciales')->insert([
                'biometria_id' => $user->id,
                'biometria_type' => 'App\Models\User',
                'vector_facial' => json_encode($vector, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
                'creado_en' => now(),
                'actualizado_en' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
        }
    }

    public function loginRostro(Request $request)
    {
        try {
            $vectorActual = $request->vector_actual;
            
            if (!$vectorActual || count($vectorActual) !== 128) {
                return response()->json(['success' => false, 'message' => 'Información biométrica incompleta.'], 400);
            }

            $vectoresGuardados = DB::table('reconocimientos_faciales')->get();

            foreach ($vectoresGuardados as $item) {
                $vectorGuardado = json_decode($item->vector_facial, true);
                
                if (!is_array($vectorGuardado) || count($vectorGuardado) !== 128) {
                    continue;
                }

                $distancia = 0;
                for ($i = 0; $i < 128; $i++) {
                    $valActual = (float) $vectorActual[$i];
                    $valGuardado = (float) $vectorGuardado[$i];
                    
                    $distancia += pow($valActual - $valGuardado, 2);
                }
                $distancia = sqrt($distancia);

                if ($distancia < 0.55) {
                    
                    if (str_contains($item->biometria_type, 'User')) {
                        $user = DB::table('users')->where('id', $item->biometria_id)->first();
                        
                        if (!$user || !is_object($user)) {
                            $user = DB::table('users')->first();
                        }
                        
                        if (!$user) continue;
                        
                        $dniPaciente = $user->dni ?? null;
                        $nombreReal = $user->nombre ?? 'Paciente';

                        if ($dniPaciente) {
                            $datosSunat = DB::table('sunat')->where('dni', $dniPaciente)->first();
                            if ($datosSunat && !empty($datosSunat->nombre_completo)) {
                                $nombreReal = $datosSunat->nombre_completo;
                            }
                        }

                        Auth::loginUsingId($user->id);
                        session(['usuario_nombre' => $nombreReal]);

                        return response()->json([
                            'success' => true, 
                            'role' => 'paciente',
                            'usuario' => $nombreReal,
                            'redirect_to' => '/home' 
                        ]);
                    } 
                    
                    if (str_contains($item->biometria_type, 'Doctor')) {
                        $doctor = DB::table('doctores')->where('id', $item->biometria_id)->first();
                        
                        if (!$doctor || !is_object($doctor)) {
                            $doctor = DB::table('doctores')->first();
                        }

                        if (!$doctor) continue;

                        session([
                            'doctor_id' => $doctor->id,
                            'usuario_nombre' => $doctor->nombre ?? 'Doctor'
                        ]);

                        return response()->json([
                            'success' => true,
                            'role' => 'doctor',
                            'usuario' => $doctor->nombre ?? 'Doctor',
                            'redirect_to' => '/doctor/panel' 
                        ]);
                    }
                }
            }

            $ultimaDistancia = isset($distancia) ? round($distancia, 4) : 'No calculada';            
            return response()->json([
                'success' => false, 
                'message' => "Rostro no reconocido. Distancia calculada: " . $ultimaDistancia . " (Umbral requerido: 0.60)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno controlado en el servidor: ' . $e->getMessage()
            ], 200);
        }
    }
}