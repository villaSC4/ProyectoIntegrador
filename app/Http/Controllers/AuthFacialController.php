<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthFacialController extends Controller
{
    private const FACE_MATCH_THRESHOLD = 0.50;

    public function consultarDni($dni)
    {
        $persona = DB::table('sunat')->where('dni', $dni)->first();

        if (!$persona) {
            return response()->json(['success' => false, 'message' => 'DNI no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'nombre' => $persona->nombre_completo,
            'fecha' => date('d/m/Y', strtotime($persona->fecha_nacimiento))
        ]);
    }

    public function finalizarRegistro(Request $request)
    {
        $vector = $request->input('face_vector');
        if (is_string($vector)) {
            $vector = json_decode($vector, true);
        }

        if (!is_array($vector) || count($vector) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'La informacion biometrica esta incompleta.'
            ], 422);
        }

        if (User::where('dni', $request->input('dni'))->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este DNI ya esta registrado.'
            ]);
        }

        DB::beginTransaction();

        try {

            $user = User::create([
                'dni' => $request->input('dni'),
                'nombre' => $request->input('nombre'),
                'celular' => $request->input('celular'),
                'email' => $request->input('email')
            ]);

            DB::table('reconocimientos_faciales')->insert([
                'biometria_id' => $user->id,
                'biometria_type' => User::class,
                'vector_facial' => json_encode($vector, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
                'creado_en' => now(),
                'actualizado_en' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function loginRostro(Request $request)
    {
        try {
            $vectoresActuales = $request->input('vector_actuales');
            if (!is_array($vectoresActuales)) {
                $vectoresActuales = [$request->input('vector_actual')];
            }

            $vectoresValidos = array_values(array_filter(
                $vectoresActuales,
                fn ($vector) => is_array($vector) && count($vector) === 128
            ));

            if (!$vectoresValidos) {
                return response()->json([
                    'success' => false,
                    'message' => 'Informacion biometrica incompleta.'
                ], 400);
            }

            $mejorCoincidencia = null;
            $mejorDistancia = INF;

            foreach (DB::table('reconocimientos_faciales')->get() as $item) {
                if ($item->biometria_type === User::class) {
                    $cuentaExiste = DB::table('users')
                        ->where('id', $item->biometria_id)
                        ->exists();
                } elseif ($item->biometria_type === Doctor::class) {
                    $cuentaExiste = DB::table('doctores')
                        ->where('id', $item->biometria_id)
                        ->exists();
                } else {
                    continue;
                }

                if (!$cuentaExiste) {
                    continue;
                }

                $vectorGuardado = json_decode($item->vector_facial, true);

                if (!is_array($vectorGuardado) || count($vectorGuardado) !== 128) {
                    continue;
                }

                $distancias = [];
                foreach ($vectoresValidos as $vectorActual) {
                    $distanciaActual = 0.0;
                    for ($i = 0; $i < 128; $i++) {
                        $diferencia = (float) $vectorActual[$i] - (float) $vectorGuardado[$i];
                        $distanciaActual += $diferencia * $diferencia;
                    }
                    $distancias[] = sqrt($distanciaActual);
                }

                sort($distancias, SORT_NUMERIC);
                $distancia = $distancias[intdiv(count($distancias), 2)];

                if ($distancia < $mejorDistancia) {
                    $mejorDistancia = $distancia;
                    $mejorCoincidencia = $item;
                }
            }

            if (!$mejorCoincidencia || $mejorDistancia >= self::FACE_MATCH_THRESHOLD) {
                $distanciaTexto = is_finite($mejorDistancia)
                    ? round($mejorDistancia, 4)
                    : 'No calculada';

                return response()->json([
                    'success' => false,
                    'message' => 'Rostro no reconocido. Distancia minima: ' . $distanciaTexto
                        . ' (umbral requerido: ' . self::FACE_MATCH_THRESHOLD . ')'
                ]);
            }

            if ($mejorCoincidencia->biometria_type === User::class) {
                $user = DB::table('users')
                    ->where('id', $mejorCoincidencia->biometria_id)
                    ->first();

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El rostro encontrado no tiene una cuenta de paciente asociada.'
                    ]);
                }

                $nombreReal = $user->nombre ?? 'Paciente';
                if (!empty($user->dni)) {
                    $datosSunat = DB::table('sunat')->where('dni', $user->dni)->first();
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

            if ($mejorCoincidencia->biometria_type === Doctor::class) {
                $doctor = DB::table('doctores')
                    ->where('id', $mejorCoincidencia->biometria_id)
                    ->first();

                if (!$doctor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El rostro encontrado no tiene un doctor asociado.'
                    ]);
                }

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

            return response()->json([
                'success' => false,
                'message' => 'El registro facial tiene un tipo de cuenta no valido.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno controlado en el servidor.'
            ], 500);
        }
    }
}
