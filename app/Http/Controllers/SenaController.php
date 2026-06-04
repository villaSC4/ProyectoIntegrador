<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SenaController extends Controller
{
    public function reconocerSena(Request $request)
    {
        try {
            $puntos = $request->puntos; 

            if (!$puntos || count($puntos) < 21) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Mano no detectada o incompleta'
                ], 400);
            }

            $puntaIndice  = $puntos[8];
            $puntaMedio   = $puntos[12];
            $puntaAnular  = $puntos[16];
            $puntaMenique = $puntos[20];

            $baseIndice  = $puntos[6];
            $baseMedio   = $puntos[10];
            $baseAnular  = $puntos[14];
            $baseMenique = $puntos[18];

            $indiceArriba  = $puntaIndice['y']  < $baseIndice['y'];
            $medioArriba   = $puntaMedio['y']   < $baseMedio['y'];
            $anularArriba  = $puntaAnular['y']  < $baseAnular['y'];
            $meniqueArriba = $puntaMenique['y'] < $baseMenique['y'];

            $especialidadId = null;
            
            if ($indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba) {
                $especialidadId = 1; 
            }
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba) {
                $especialidadId = 2;
            }
            elseif ($indiceArriba && $medioArriba && $anularArriba && !$meniqueArriba) {
                $especialidadId = 3;
            }
            elseif ($indiceArriba && $medioArriba && $anularArriba && $meniqueArriba) {
                $especialidadId = 4;
            }

            if ($especialidadId) {
                $especialidad = DB::table('especialidades')->where('id', $especialidadId)->first();

                if ($especialidad) {
                    return response()->json([
                        'success' => true,
                        'id' => $especialidad->id,
                        'nombre' => $especialidad->nombre
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'id' => null,
                'nombre' => 'Detectando seña...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error en procesamiento: ' . $e->getMessage()
            ], 500);
        }
    }
}