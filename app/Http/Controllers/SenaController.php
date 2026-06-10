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

            $indiceArriba  = ($puntos[8]['y']  < $puntos[7]['y'])  && ($puntos[8]['y']  < $puntos[6]['y'])  && ($puntos[8]['y']  < $puntos[5]['y']);
            $medioArriba   = ($puntos[12]['y'] < $puntos[11]['y']) && ($puntos[12]['y'] < $puntos[10]['y']) && ($puntos[12]['y'] < $puntos[9]['y']);
            $anularArriba  = ($puntos[16]['y'] < $puntos[15]['y']) && ($puntos[16]['y'] < $puntos[14]['y']) && ($puntos[16]['y'] < $puntos[13]['y']);
            $meniqueArriba = ($puntos[20]['y'] < $puntos[19]['y']) && ($puntos[20]['y'] < $puntos[18]['y']) && ($puntos[20]['y'] < $puntos[17]['y']);

            $distPulgarIndice = $this->calcularDistancia($p_pulgar, $p_indice);
            $distPulgarMedio  = $this->calcularDistancia($p_pulgar, $p_medio);
            $distIndiceMedio  = $this->calcularDistancia($p_indice, $p_medio);

            $especialidadId = null;

            // 👆 ID 1: Dermatolog (Forma de "Garra" hacia abajo)
            if ($puntos[8]['y'] > $puntos[5]['y'] && $puntos[12]['y'] > $puntos[9]['y'] && $puntos[16]['y'] > $puntos[13]['y'] 
                && $puntos[8]['y'] > $puntos[4]['y'] && $distPulgarIndice > 0.08) {
                $especialidadId = 1;
            }

            // 👌 ID 5: Odontolog (Pinza Pulgar-Índice cerrada, los otros tres dedos COMPLETAMENTE CERRADOS)
            elseif ($distPulgarIndice < 0.045 && !$medioArriba && !$anularArriba && !$meniqueArriba) {
                $especialidadId = 5;
            }

            // 👌 ID 6: Oftalmolog (Pinza Pulgar-Índice cerrada, pero los otros tres dedos ABIERTOS Y ESTIRADOS)
            elseif ($distPulgarIndice < 0.045 && $medioArriba && $anularArriba && $meniqueArriba) {
                $especialidadId = 6;
            }

            // ☝️ ID 7: Neurolog (Solo el Índice arriba controlando el eje vertical, bien alejado del pulgar)
            elseif ($indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba && $distPulgarIndice >= 0.07) {
                $especialidadId = 7;
            }

            // 🫴 ID 4: Cardiolog (Letra "C" - Arco abierto horizontal cóncavo)
            elseif (!$indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba 
                    && $distPulgarIndice >= 0.09 && $distPulgarIndice <= 0.18 && $p_pulgar['x'] < $p_indice['x']) {
                $especialidadId = 4;
            }

            // ✌️ ID 8: Traumatolog (Dedos Índice y Medio arriba BIEN SEPARADOS en "V" ancha)
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba && $distIndiceMedio > 0.065) {
                $especialidadId = 8;
            }

            // 🤞 ID 2: Ginecolog (Dedos Índice y Medio arriba JUNTOS en postura natural/relajada)
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba 
                    && $distIndiceMedio <= 0.04 && $distIndiceMedio > 0.022) {
                $especialidadId = 2;
            }

            // 🔟 ID 10: Urolog (Dedos Índice y Medio PEGADOS RÍGIDAMENTE e hiper-estirados hacia el techo)
            elseif ($indiceArriba && $medioArriba && !$anularArriba && !$meniqueArriba 
                    && $distIndiceMedio <= 0.022 && $p_medio['y'] < $puntos[10]['y'] * 0.55) {
                $especialidadId = 10;
            }

            // ✋ ID 3: Pediatr (Palma totalmente extendida frente a la cámara)
            elseif ($indiceArriba && $medioArriba && $anularArriba && $meniqueArriba && $distIndiceMedio > 0.04) {
                $especialidadId = 3;
            }

            // 🫵 ID 9: Psiquiatr (Letra "P" - Índice horizontal al frente, Medio apuntando abajo y Pulgar en pinza)
            elseif ($indiceArriba && !$medioArriba && !$anularArriba && !$meniqueArriba 
                    && $p_medio['y'] > $puntos[10]['y'] && $distPulgarMedio < 0.055) {
                $especialidadId = 9;
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
        return sqrt(
            pow($punto1['x'] - $punto2['x'], 2) + 
            pow($punto1['y'] - $punto2['y'], 2) + 
            pow($punto1['z'] - $punto2['z'], 2)
        );
    }
}