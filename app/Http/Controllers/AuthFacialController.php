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

            $user->reconocimientoFacial()->create([
                'vector_facial' => json_encode($request->face_vector)
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
        $vectorActual = $request->vector_actual;
        
        $vectoresGuardados = ReconocimientoFacial::all();

        foreach ($vectoresGuardados as $item) {
            $vectorGuardado = json_decode($item->vector_facial);
            if (!$vectorGuardado) continue;

            $distancia = 0;
            for ($i = 0; $i < count($vectorActual); $i++) {
                $distancia += pow($vectorActual[$i] - $vectorGuardado[$i], 2);
            }
            $distancia = sqrt($distancia);

            if ($distancia < 0.7) {
                
                if ($item->biometria_type === User::class) {
                    $user = $item->biometria; 
                    
                    $datosPersonales = DB::table('sunat')->where('dni', $user->dni)->first();
                    $nombreReal = $datosPersonales ? $datosPersonales->nombre_completo : 'Paciente';

                    Auth::loginUsingId($user->id);
                    session(['usuario_nombre' => $nombreReal]);

                    return response()->json([
                        'success' => true, 
                        'role' => 'paciente',
                        'usuario' => $nombreReal 
                    ]);
                } 
                
                if ($item->biometria_type === Doctor::class) {
                    $doctor = $item->biometria; 

                    session(['usuario_nombre' => $doctor->nombre]);

                    return response()->json([
                        'success' => true,
                        'role' => 'doctor',
                        'usuario' => $doctor->nombre
                    ]);
                }
            }
        }
        return response()->json(['success' => false, 'message' => 'Rostro no reconocido en el sistema'], 401);
    }
}