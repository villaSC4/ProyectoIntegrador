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
                    'message' => 'Mano no detectada de forma clara'
                ], 400);
            }

            $p_pulgar   = $puntos[4];  $b_pulgar   = $puntos[2];
            $p_indice   = $puntos[8];  $b_indice   = $puntos[6];
            $p_medio    = $puntos[12]; $b_medio    = $puntos[10];
            $p_anular   = $puntos[16]; $b_anular   = $puntos[14];
            $p_menique  = $puntos[20]; $b_menique  = $puntos[18];
            $muneca     = $puntos[0];

            $indiceArriba  = $p_indice['y']  < $b_indice['y'];
            $medioArriba   = $p_medio['y']   < $b_medio['y'];
            $anularArriba  = $p_anular['y']  < $b_anular['y'];
            $meniqueArriba = $p_menique['y'] < $b_menique['y'];

            $distPulgarIndice = $this->calcularDistancia($p_pulgar, $p_indice);
            $distPulgarMedio  = $this->calcularDistancia($p_pulgar, $p_medio);
            $distIndiceMedio  = $this->calcularDistancia($p_indice, $p_medio);
            $distMedioAnular  = $this->calcularDistancia($p_medio, $p_anular);
            $distPulgarMenique = $this->calcularDistancia($p_pulgar, $p_menique);

            $señaDetectada = null;
            $especialidadId = null;

            if ($p_indice['y'] > $b_indice['y'] && $p_medio['y'] > $b_medio['y'] && $p_anular['y'] > $b_anular['y']
                && $p_indice['y'] > $p_pulgar['y'] && $distPulgarIndice > 0.08 && $distIndiceMedio < 0.07) {
                
                $especialidadId = 1;
            }

            // ID 5: Odontolog (Forma de "Pinza de Diente" - Pulgar e Índice arqueados casi tocándose, Medio/Anular/Meñique bien cerrados)
            elseif ($indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba 
                    && $distPulgarIndice > 0.02 && $distPulgarIndice < 0.06) {
                
                $especialidadId = 5;
            }

            // ID 7: Neurolog (Forma de "Cerebro/Pensamiento" - Solo el Índice estirado hacia arriba vibrando solo, los demás dedos aplastados contra la palma)
            elseif ($indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba 
                    && $p_pulgar['y'] > $b_pulgar['y'] && $distPulgarIndice >= 0.07) {
                
                $especialidadId = 7;
            }

            //  ID 4: Cardiolog (Forma de "Medio Corazón" - Mano cóncava, todos los dedos curvados juntos hacia adentro, el pulgar cierra la base)
            elseif (!$indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba 
                    && $distPulgarIndice < 0.05 && $distPulgarMenique < 0.07) {
                
                $especialidadId = 4;
            }

            // ID 2: Ginecolog (Índice y Medio arriba bien pegados, Anular y Meñique abajo)
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba && $distIndiceMedio <= 0.04) {
                $especialidadId = 2;
            }

            // ID 3: Pediatr (Mano completamente abierta y dedos estirados hacia arriba)
            elseif ($indiceArriba && $medioArriba && $anularArriba && $meniqueArriba && $distIndiceMedio > 0.05) {
                $especialidadId = 3;
            }

            // ID 6: Oftalmolog (Forma de "Anteojo" - Círculo cerrado entre Pulgar e Índice, los otros 3 dedos estirados arriba)
            elseif (!$indiceArriba && $medioArriba && $anularArriba && $meniqueArriba && $distPulgarIndice < 0.03) {
                $especialidadId = 6;
            }

            //  ID 8: Traumatolog (Forma de "Hueso/Quiebre" - Índice y Medio arriba doblados a la mitad, Anular y Meñique cerrados)
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba && $p_indice['y'] > $b_indice['y'] * 0.9 && $distIndiceMedio < 0.05) {
                $especialidadId = 8;
            }

            //  ID 9: Psiquiatr (Letra "P" pura - Índice al frente horizontal, Medio apuntando abajo, Pulgar tocando el medio)
            elseif ($indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba && $p_medio['y'] > $b_medio['y'] && $distPulgarMedio < 0.05) {
                $especialidadId = 9;
            }

            //ID 10: Urolog (Letra "U" extrema - Índice y Medio pegados apuntando rígidamente al techo)
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba && $distIndiceMedio < 0.03 && $p_medio['y'] < $b_medio['y'] * 0.6) {
                $especialidadId = 10;
            }
            if ($especialidadId) {
                $especialidad = DB::table('especialidades')->where('id', $especialidadId)->first();
                if ($especialidad) {
                    return response()->json([
                        'success' => true,
                        'id' => $especialidad->id,
                        'nombre' => $especialidad->nombre,
                        'tipo' => 'especialidad'
                    ]);
                }
            }

            if ($señaDetectada) {
                return response()->json([
                    'success' => true,
                    'id' => null,
                    'nombre' => $señaDetectada,
                    'tipo' => 'vocabulario'
                ]);
            }

            return response()->json([
                'success' => true,
                'id' => null,
                'nombre' => 'Interpretando señas en tiempo real...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error en procesamiento biométrico: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calcularDistancia($punto1, $punto2)
    {
        $x = pow($punto1['x'] - $punto2['x'], 2);
        $y = pow($punto1['y'] - $punto2['y'], 2);
        $z = pow($punto1['z'] - $punto2['z'], 2);
        return sqrt($x + $y + $z);
    }
}