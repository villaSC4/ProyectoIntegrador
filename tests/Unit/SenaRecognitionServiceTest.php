<?php

namespace Tests\Unit;

use App\Services\SenaRecognitionService;
use PHPUnit\Framework\TestCase;

class SenaRecognitionServiceTest extends TestCase
{
    public function test_clasifica_una_secuencia_completa_con_dos_manos(): void
    {
        $servicio = new SenaRecognitionService();
        $objetivo = $this->secuencia('horizontal', 2);
        $diferente = $this->secuencia('vertical', 1);
        $muestras = collect();

        for ($i = 0; $i < 8; $i++) {
            $muestras->push($this->muestra($servicio, 'como_te_llamas', 'Como te llamas', $objetivo, $i + 1));
            $muestras->push($this->muestra($servicio, 'tengo_dolor', 'Tengo dolor', $diferente, $i + 10));
        }

        $resultado = $servicio->clasificar($objetivo, $muestras);

        $this->assertNotNull($resultado);
        $this->assertSame('como_te_llamas', $resultado['codigo']);
        $this->assertGreaterThanOrEqual(0.24, $resultado['confianza']);
    }

    public function test_rechaza_una_clase_con_menos_de_tres_muestras(): void
    {
        $servicio = new SenaRecognitionService();
        $secuencia = $this->secuencia('horizontal', 2);
        $muestras = collect([
            $this->muestra($servicio, 'hola', 'Hola', $secuencia, 1),
            $this->muestra($servicio, 'hola', 'Hola', $secuencia, 2),
        ]);

        $this->assertNull($servicio->clasificar($secuencia, $muestras));
    }

    public function test_tolera_perder_una_mano_en_algunos_cuadros_de_la_camara(): void
    {
        $servicio = new SenaRecognitionService();
        $objetivo = $this->secuencia('horizontal', 2);
        $alternativa = $this->secuencia('vertical', 1);
        $muestras = collect();

        for ($i = 0; $i < 8; $i++) {
            $muestras->push($this->muestra($servicio, 'como_te_llamas', 'Como te llamas', $objetivo, $i + 1));
            $muestras->push($this->muestra($servicio, 'tengo_dolor', 'Tengo dolor', $alternativa, $i + 10));
        }

        $capturaIntermitente = $objetivo;
        foreach ($capturaIntermitente as $indice => &$frame) {
            if ($indice % 3 === 0) {
                array_pop($frame['manos']);
            }
        }
        unset($frame);

        $resultado = $servicio->clasificar($capturaIntermitente, $muestras);

        $this->assertNotNull($resultado);
        $this->assertTrue($resultado['aceptada']);
        $this->assertSame('como_te_llamas', $resultado['codigo']);
    }

    public function test_no_confirma_un_puno_cerrado_como_una_sena_entrenada(): void
    {
        $servicio = new SenaRecognitionService();
        $saludo = $this->secuencia('horizontal', 1);
        $otraSena = $this->secuencia('vertical', 1);
        $muestras = collect();

        for ($i = 0; $i < 8; $i++) {
            $muestras->push($this->muestra($servicio, 'hola', 'Hola', $saludo, $i + 1));
            $muestras->push($this->muestra($servicio, 'dolor', 'Dolor', $otraSena, $i + 20));
        }

        $resultado = $servicio->clasificar($this->secuenciaPunoCerrado(), $muestras);

        $this->assertNotNull($resultado);
        $this->assertFalse($resultado['aceptada']);
        $this->assertNull($resultado['codigo']);
    }

    public function test_distingue_la_misma_forma_de_mano_por_su_zona_corporal(): void
    {
        $servicio = new SenaRecognitionService();
        $cercaBoca = $this->secuenciaEnZonaCorporal('boca');
        $cercaHombro = $this->secuenciaEnZonaCorporal('hombro');
        $muestras = collect();

        for ($i = 0; $i < 8; $i++) {
            $muestras->push($this->muestra($servicio, 'decir', 'Decir', $cercaBoca, $i + 1));
            $muestras->push($this->muestra($servicio, 'buenos', 'Buenos', $cercaHombro, $i + 20));
        }

        $resultadoBoca = $servicio->clasificar($cercaBoca, $muestras);
        $resultadoHombro = $servicio->clasificar($cercaHombro, $muestras);

        $this->assertNotNull($resultadoBoca);
        $this->assertNotNull($resultadoHombro);
        $this->assertSame('decir', $resultadoBoca['codigo']);
        $this->assertSame('buenos', $resultadoHombro['codigo']);
    }

    public function test_rechaza_un_movimiento_aprendido_como_ninguna_sena(): void
    {
        $servicio = new SenaRecognitionService();
        $saludo = $this->secuencia('horizontal', 1);
        $dolor = $this->secuencia('vertical', 1);
        $ninguna = $this->secuenciaPunoCerrado();
        $muestras = collect();

        for ($i = 0; $i < 8; $i++) {
            $muestras->push($this->muestra($servicio, 'hola', 'Hola', $saludo, $i + 1));
            $muestras->push($this->muestra($servicio, 'dolor', 'Dolor', $dolor, $i + 20));
        }
        for ($i = 0; $i < 3; $i++) {
            $muestras->push($this->muestra(
                $servicio,
                SenaRecognitionService::NEGATIVE_CODE,
                'Ninguna sena',
                $ninguna,
                $i + 50
            ));
        }

        $resultado = $servicio->clasificar($ninguna, $muestras);

        $this->assertNotNull($resultado);
        $this->assertFalse($resultado['aceptada']);
        $this->assertTrue($resultado['es_negativa']);
        $this->assertNull($resultado['codigo']);
    }

    private function muestra(
        SenaRecognitionService $servicio,
        string $codigo,
        string $texto,
        array $secuencia,
        int $id
    ): object {
        $analisis = $servicio->analizarSecuencia($secuencia);

        return (object) [
            'id' => $id,
            'codigo' => $codigo,
            'texto' => $texto,
            'puntos' => json_encode($secuencia),
            'features' => json_encode($servicio->crearFeatures($secuencia, $analisis)),
            'calidad' => $analisis['calidad'],
            'duracion_ms' => $analisis['duracion_ms'],
            'manos_max' => $analisis['manos_max'],
            'tipo_movimiento' => $analisis['tipo_movimiento'],
        ];
    }

    private function secuencia(string $movimiento, int $cantidadManos): array
    {
        $frames = [];
        for ($frame = 0; $frame < 24; $frame++) {
            $manos = [];
            for ($mano = 0; $mano < $cantidadManos; $mano++) {
                $puntos = [];
                for ($punto = 0; $punto < 21; $punto++) {
                    $avanceX = $movimiento === 'horizontal' ? $frame * 0.004 : 0;
                    $avanceY = $movimiento === 'vertical' ? $frame * 0.004 : 0;
                    $puntos[] = [
                        'x' => 0.28 + ($mano * 0.30) + (($punto % 4) * 0.012) + $avanceX,
                        'y' => 0.70 - (intdiv($punto, 4) * 0.025) - $avanceY,
                        'z' => -($punto * 0.001),
                    ];
                }
                $manos[] = $puntos;
            }

            $frames[] = [
                't' => $frame * 80,
                'manos' => $manos,
                'rostro' => ['yaw' => 0.01, 'pitch' => 0.15, 'boca' => 0.03, 'cejas' => 0.08],
                'cuerpo' => ['ancho_hombros' => 0.31, 'inclinacion' => 0.01],
            ];
        }

        return $frames;
    }

    private function secuenciaPunoCerrado(): array
    {
        $frames = $this->secuencia('horizontal', 1);

        foreach ($frames as &$frame) {
            $mano = &$frame['manos'][0];
            $muneca = $mano[0];

            foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20] as $indice) {
                $mano[$indice]['x'] = $muneca['x'] + (($indice % 3) - 1) * 0.012;
                $mano[$indice]['y'] = $muneca['y'] - 0.055 - (intdiv($indice, 4) * 0.004);
                $mano[$indice]['z'] = -0.005;
            }

            // Conserva una palma medible, pero deja todos los dedos recogidos.
            $mano[9]['y'] = $muneca['y'] - 0.09;
            $mano[5]['x'] = $muneca['x'] - 0.055;
            $mano[17]['x'] = $muneca['x'] + 0.055;
        }
        unset($frame, $mano);

        return $frames;
    }

    private function secuenciaEnZonaCorporal(string $zona): array
    {
        $frames = $this->secuencia('horizontal', 1);

        foreach ($frames as $indiceFrame => &$frame) {
            $frame['rostro'] = [
                'x' => 0.50,
                'y' => 0.32,
                'z' => 0.0,
                'yaw' => 0.0,
                'pitch' => 0.0,
                'boca' => 0.02,
                'cejas' => 0.08,
                'escala_rostro' => 0.16,
                'boca_x' => 0.50,
                'boca_y' => 0.39,
                'boca_z' => 0.0,
                'frente_x' => 0.50,
                'frente_y' => 0.23,
                'frente_z' => 0.0,
            ];
            $frame['cuerpo'] = [
                'hombro_x' => 0.50,
                'hombro_y' => 0.54,
                'ancho_hombros' => 0.32,
                'inclinacion' => 0.0,
                'hombro_izq_x' => 0.34,
                'hombro_izq_y' => 0.54,
                'hombro_izq_z' => 0.0,
                'hombro_der_x' => 0.66,
                'hombro_der_y' => 0.54,
                'hombro_der_z' => 0.0,
                'pecho_x' => 0.50,
                'pecho_y' => 0.66,
                'pecho_z' => 0.0,
            ];

            $munecaX = $zona === 'boca' ? 0.49 : 0.34;
            $munecaY = $zona === 'boca' ? 0.47 : 0.62;
            $munecaX += $indiceFrame * 0.0015;
            $dx = $munecaX - $frame['manos'][0][0]['x'];
            $dy = $munecaY - $frame['manos'][0][0]['y'];

            foreach ($frame['manos'][0] as &$punto) {
                $punto['x'] += $dx;
                $punto['y'] += $dy;
            }
            unset($punto);
        }
        unset($frame);

        return $frames;
    }
}
