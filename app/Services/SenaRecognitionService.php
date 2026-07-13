<?php

namespace App\Services;

use Illuminate\Support\Collection;

class SenaRecognitionService
{
    public const MODEL_VERSION = 'v4.0';
    public const MINIMUM_SAMPLES_PER_PHRASE = 8;
    public const MINIMUM_TRAINING_QUALITY = 0.65;
    public const NEGATIVE_CODE = '__ninguna_sena__';

    private const SAMPLE_FRAMES = 12;
    private const MAX_SEQUENCE_FRAMES = 96;
    private const MAX_DTW_SAMPLES_PER_CLASS = 8;
    private const MAX_DTW_CANDIDATES = 48;
    private const MINIMUM_CONFIDENCE = 0.70;
    private const MINIMUM_INTERMITTENT_TWO_HAND_CONFIDENCE = 0.47;

    public function prepararSecuencia(mixed $secuencia, array $manosActuales = []): array
    {
        $entrada = is_array($secuencia) ? $secuencia : [];

        if (!$entrada && $manosActuales) {
            $entrada = [['t' => 0, 'manos' => $manosActuales]];
        }

        $frames = [];
        foreach ($entrada as $indice => $frame) {
            if (!is_array($frame)) {
                continue;
            }

            $manos = [];
            foreach (array_slice(is_array($frame['manos'] ?? null) ? $frame['manos'] : [], 0, 2) as $mano) {
                $mano = $this->sanitizarMano($mano);
                if ($mano) {
                    $manos[] = $mano;
                }
            }

            usort($manos, fn ($a, $b) => ($a[0]['x'] ?? 0) <=> ($b[0]['x'] ?? 0));

            $frames[] = [
                't' => is_numeric($frame['t'] ?? null) ? (float) $frame['t'] : $indice * 33.0,
                'manos' => $manos,
                'rostro' => $this->sanitizarContexto($frame['rostro'] ?? $frame['cabeza'] ?? null, [
                    'x', 'y', 'z', 'yaw', 'pitch', 'boca', 'cejas',
                    'escala_rostro',
                    'boca_x', 'boca_y', 'boca_z',
                    'frente_x', 'frente_y', 'frente_z',
                    'cachete_izq_x', 'cachete_izq_y', 'cachete_izq_z',
                    'cachete_der_x', 'cachete_der_y', 'cachete_der_z',
                ]),
                'cuerpo' => $this->sanitizarContexto($frame['cuerpo'] ?? null, [
                    'hombro_x', 'hombro_y', 'ancho_hombros', 'inclinacion',
                    'hombro_izq_x', 'hombro_izq_y', 'hombro_izq_z',
                    'hombro_der_x', 'hombro_der_y', 'hombro_der_z',
                    'pecho_x', 'pecho_y', 'pecho_z',
                    'codo_izq_x', 'codo_izq_y', 'codo_der_x', 'codo_der_y',
                ]),
            ];
        }

        if (!$frames) {
            return [];
        }

        $hayManosEnSecuencia = collect($frames)->contains(
            fn ($frame) => !empty($frame['manos'])
        );
        $primero = null;
        $ultimo = null;
        foreach ($frames as $indice => $frame) {
            $tieneSenal = $hayManosEnSecuencia
                ? !empty($frame['manos'])
                : $this->frameTieneSenal($frame);

            if ($tieneSenal) {
                $primero ??= $indice;
                $ultimo = $indice;
            }
        }

        if ($primero === null) {
            return [];
        }

        $frames = array_slice(
            $frames,
            max(0, $primero - 2),
            min(count($frames) - max(0, $primero - 2), ($ultimo - $primero + 1) + 4)
        );

        $inicio = $frames[0]['t'];
        foreach ($frames as &$frame) {
            $frame['t'] = max(0, round($frame['t'] - $inicio));
        }
        unset($frame);

        return $this->muestrear($frames, self::MAX_SEQUENCE_FRAMES);
    }

    public function analizarSecuencia(array $secuencia): array
    {
        $total = count($secuencia);
        $framesConManos = array_values(array_filter($secuencia, fn ($frame) => count($frame['manos'] ?? []) > 0));
        $framesVisibles = count($framesConManos);
        $framesConDosManos = count(array_filter(
            $secuencia,
            fn ($frame) => count($frame['manos'] ?? []) >= 2
        ));
        $framesContexto = count(array_filter($secuencia, fn ($frame) => !empty($frame['rostro']) || !empty($frame['cuerpo'])));
        $manosMax = 0;

        foreach ($secuencia as $frame) {
            $manosMax = max($manosMax, min(2, count($frame['manos'] ?? [])));
        }

        $duracion = $total > 1
            ? (int) max(0, ($secuencia[$total - 1]['t'] ?? 0) - ($secuencia[0]['t'] ?? 0))
            : 0;
        $movimientoManos = $this->movimientoManos($secuencia);
        $movimientoRostro = $this->movimientoRostro($secuencia);
        $movimiento = min(1, ($movimientoManos * 0.82) + ($movimientoRostro * 0.18));
        $ratioVisible = $total > 0 ? $framesVisibles / $total : 0;
        $ratioDosManos = $total > 0 ? $framesConDosManos / $total : 0;
        $hayGestoCorporal = $movimientoRostro >= 0.16 && $framesContexto >= 8;

        $factorFrames = min(1, max($framesVisibles, $hayGestoCorporal ? $framesContexto : 0) / 18);
        $factorDuracion = $duracion >= 650 && $duracion <= 6500
            ? 1
            : ($duracion < 650 ? min(1, $duracion / 650) : max(0.35, 1 - (($duracion - 6500) / 7000)));
        $factorVisibilidad = $framesVisibles > 0 ? min(1, $ratioVisible / 0.72) : ($hayGestoCorporal ? 0.9 : 0);
        $factorContexto = min(1, $framesContexto / max($total, 1));
        $calidad = round(min(1,
            ($factorFrames * 0.35)
            + ($factorDuracion * 0.25)
            + ($factorVisibilidad * 0.30)
            + ($factorContexto * 0.10)
        ), 4);

        $utilizable = ($framesVisibles >= 5 || $hayGestoCorporal) && $total >= 7;
        $validaEntrenamiento = ($framesVisibles >= 10 || $hayGestoCorporal)
            && $duracion >= 550
            && $duracion <= 9000
            && $calidad >= self::MINIMUM_TRAINING_QUALITY;

        return [
            'utilizable' => $utilizable,
            'valida_para_entrenar' => $validaEntrenamiento,
            'calidad' => $calidad,
            'duracion_ms' => $duracion,
            'cantidad_frames' => $total,
            'frames_visibles' => $framesVisibles,
            'ratio_visibilidad' => round($ratioVisible, 4),
            'ratio_dos_manos' => round($ratioDosManos, 4),
            'manos_max' => $manosMax,
            'movimiento' => round($movimiento, 4),
            'movimiento_manos' => round($movimientoManos, 4),
            'movimiento_rostro' => round($movimientoRostro, 4),
            'tipo_movimiento' => $movimiento >= 0.18 ? 'dinamica' : 'estatica',
            'mensaje' => $framesVisibles === 0 && !$hayGestoCorporal
                ? 'Coloque una o dos manos dentro del recuadro.'
                : 'Observando la sena completa...',
            'mensaje_entrenamiento' => $this->mensajeCalidad(
                $framesVisibles,
                $duracion,
                $calidad,
                $hayGestoCorporal
            ),
        ];
    }

    public function crearFeatures(array $secuencia, ?array $analisis = null): array
    {
        $analisis ??= $this->analizarSecuencia($secuencia);

        return [
            'version' => self::MODEL_VERSION,
            'descriptor' => $this->descriptorSecuencia($secuencia),
            'trayectoria' => $this->descriptorTrayectoria($secuencia),
            'analisis' => $analisis,
        ];
    }

    public function clasificar(array $secuencia, Collection $muestras): ?array
    {
        if ($muestras->isEmpty()) {
            return null;
        }

        $actual = $this->crearFeatures($secuencia);
        $preseleccion = [];
        $totalesPorCodigo = [];

        foreach ($muestras as $muestra) {
            $guardadas = json_decode($muestra->features ?? '[]', true);
            if (!is_array($guardadas) || ($guardadas['version'] ?? null) !== self::MODEL_VERSION) {
                continue;
            }

            $totalesPorCodigo[$muestra->codigo] = ($totalesPorCodigo[$muestra->codigo] ?? 0) + 1;
            $rapida = $this->distanciaVector(
                $actual['descriptor'],
                is_array($guardadas['descriptor'] ?? null) ? $guardadas['descriptor'] : []
            );
            $rapida += $this->penalizacionCompatibilidad(
                $actual['analisis'],
                $guardadas['analisis'] ?? []
            ) * 0.35;

            $preseleccion[] = [
                'muestra' => $muestra,
                'features' => $guardadas,
                'rapida' => $rapida,
            ];
        }

        if (!$preseleccion) {
            return null;
        }

        usort($preseleccion, fn ($a, $b) => $a['rapida'] <=> $b['rapida']);
        $conteoPorClase = [];
        $preseleccion = array_values(array_filter(
            $preseleccion,
            function ($candidato) use (&$conteoPorClase) {
                $codigo = $candidato['muestra']->codigo;
                $conteo = $conteoPorClase[$codigo] ?? 0;
                if ($conteo >= self::MAX_DTW_SAMPLES_PER_CLASS) {
                    return false;
                }

                $conteoPorClase[$codigo] = $conteo + 1;
                return true;
            }
        ));
        $preseleccion = array_slice($preseleccion, 0, self::MAX_DTW_CANDIDATES);
        $resultados = [];

        foreach ($preseleccion as $candidato) {
            $muestra = $candidato['muestra'];
            $secuenciaMuestra = json_decode($muestra->puntos ?? '[]', true);
            if (!is_array($secuenciaMuestra) || !$secuenciaMuestra) {
                continue;
            }

            $dtw = $this->distanciaDtw($secuencia, $secuenciaMuestra);
            $trayectoria = $this->distanciaVector(
                $actual['trayectoria'],
                is_array($candidato['features']['trayectoria'] ?? null)
                    ? $candidato['features']['trayectoria']
                    : $this->descriptorTrayectoria($secuenciaMuestra)
            );
            $extremos = $this->distanciaExtremos($secuencia, $secuenciaMuestra);
            $compatibilidad = $this->penalizacionCompatibilidad(
                $actual['analisis'],
                $candidato['features']['analisis'] ?? []
            );

            // La forma y la trayectoria mandan. El contexto de rostro/cuerpo ayuda,
            // pero no debe invalidar una sena si una webcam pierde una mano un instante.
            $distancia = ($dtw * 0.52)
                + ($trayectoria * 0.22)
                + ($extremos * 0.14)
                + ($candidato['rapida'] * 0.12)
                + ($compatibilidad * 0.30);

            $resultados[] = [
                'codigo' => $muestra->codigo,
                'texto' => $muestra->texto,
                'distancia' => $distancia,
            ];
        }

        if (!$resultados) {
            return null;
        }

        $clases = [];
        foreach ($resultados as $resultado) {
            $clases[$resultado['codigo']]['codigo'] = $resultado['codigo'];
            $clases[$resultado['codigo']]['texto'] = $resultado['texto'];
            $clases[$resultado['codigo']]['distancias'][] = $resultado['distancia'];
        }

        $ranking = [];
        foreach ($clases as $clase) {
            sort($clase['distancias']);
            $mejores = array_slice($clase['distancias'], 0, min(5, count($clase['distancias'])));
            $base = array_slice($mejores, 0, min(3, count($mejores)));
            $promedioRobusto = array_sum($base) / max(1, count($base));
            $ranking[] = [
                'codigo' => $clase['codigo'],
                'texto' => $clase['texto'],
                'distancia' => ($promedioRobusto * 0.72) + ($mejores[0] * 0.28),
                'muestras' => $totalesPorCodigo[$clase['codigo']] ?? count($clase['distancias']),
            ];
        }

        usort($ranking, fn ($a, $b) => $a['distancia'] <=> $b['distancia']);
        $negativa = null;
        $ranking = array_values(array_filter(
            $ranking,
            function ($clase) use (&$negativa) {
                if ($clase['codigo'] === self::NEGATIVE_CODE) {
                    $negativa = $clase;
                    return false;
                }

                return true;
            }
        ));
        if (!$ranking) {
            return null;
        }

        $mejor = $ranking[0];
        $segundo = $ranking[1] ?? null;
        // Para escribir texto, el modelo debe parecerse mucho a una sena entrenada.
        // Si no llega a este nivel, es preferible no traducir nada antes que inventar una palabra.
        $umbral = $actual['analisis']['tipo_movimiento'] === 'dinamica' ? 0.82 : 0.68;
        $dosManosIntermitentes = ($actual['analisis']['manos_max'] ?? 0) >= 2
            && ($actual['analisis']['ratio_dos_manos'] ?? 0) >= 0.40
            && ($actual['analisis']['ratio_dos_manos'] ?? 0) < 0.95;
        $limiteAceptacion = $umbral * ($dosManosIntermitentes ? 0.88 : 0.74);
        $confianzaMinima = $dosManosIntermitentes
            ? self::MINIMUM_INTERMITTENT_TWO_HAND_CONFIDENCE
            : self::MINIMUM_CONFIDENCE;
        $margen = $segundo ? $segundo['distancia'] - $mejor['distancia'] : $umbral - $mejor['distancia'];
        $confianzaAbsoluta = $this->limitar(1 - ($mejor['distancia'] / $umbral));
        $confianzaMargen = $this->limitar($margen / max(0.12, $mejor['distancia'] * 0.45));
        $confianza = $this->limitar(
            ($confianzaAbsoluta * 0.60)
            + ($confianzaMargen * 0.30)
            + (($actual['analisis']['calidad'] ?? 0) * 0.10)
        );

        $suficientesMuestras = $mejor['muestras'] >= self::MINIMUM_SAMPLES_PER_PHRASE;
        $coincidenciaFuerte = $mejor['distancia'] <= $limiteAceptacion * 0.72;
        $separacionSuficiente = !$segundo || $margen >= 0.075 || $coincidenciaFuerte;
        $pareceNegativa = $negativa
            && $negativa['distancia'] <= $limiteAceptacion
            && ($negativa['distancia'] + 0.04) < $mejor['distancia'];
        $evidenciaReforzada = $mejor['muestras'] >= 10
            && $margen >= 0.08
            && $mejor['distancia'] <= ($limiteAceptacion * 0.90)
            && ($actual['analisis']['calidad'] ?? 0) >= 0.80;
        $confianzaSuficiente = $confianza >= $confianzaMinima
            || ($evidenciaReforzada && $confianza >= 0.64);
        $aceptada = $suficientesMuestras
            && $mejor['distancia'] <= $limiteAceptacion
            && $separacionSuficiente
            && $confianzaSuficiente
            && ($actual['analisis']['calidad'] ?? 0) >= 0.64
            && !$pareceNegativa;

        if (!$suficientesMuestras) {
            return null;
        }

        $alternativas = array_map(function ($item) use ($umbral) {
            return [
                'codigo' => $item['codigo'],
                'texto' => $item['texto'],
                'confianza' => round($this->limitar(1 - ($item['distancia'] / $umbral)), 3),
            ];
        }, array_slice($ranking, 0, 3));

        return [
            'codigo' => $aceptada ? $mejor['codigo'] : null,
            'codigo_candidato' => $mejor['codigo'],
            'texto' => $mejor['texto'],
            'confianza' => round($confianza, 3),
            'aceptada' => $aceptada,
            'es_negativa' => (bool) $pareceNegativa,
            'alternativas' => $alternativas,
            'diagnostico' => [
                'distancia' => round($mejor['distancia'], 4),
                'umbral' => $umbral,
                'limite_aceptacion' => round($limiteAceptacion, 4),
                'dos_manos_intermitentes' => $dosManosIntermitentes,
                'margen' => round($margen, 4),
                'muestras' => $mejor['muestras'],
                'tipo' => $actual['analisis']['tipo_movimiento'],
                'distancia_negativa' => $negativa
                    ? round($negativa['distancia'], 4)
                    : null,
                'evidencia_reforzada' => $evidenciaReforzada,
            ],
        ];
    }

    private function sanitizarMano(mixed $mano): array
    {
        if (!is_array($mano) || count($mano) < 21) {
            return [];
        }

        $puntos = [];
        foreach (array_slice($mano, 0, 21) as $punto) {
            if (!is_array($punto) || !is_numeric($punto['x'] ?? null) || !is_numeric($punto['y'] ?? null)) {
                return [];
            }

            $puntos[] = [
                'x' => round((float) $punto['x'], 6),
                'y' => round((float) $punto['y'], 6),
                'z' => round((float) ($punto['z'] ?? 0), 6),
            ];
        }

        return $puntos;
    }

    private function sanitizarContexto(mixed $contexto, array $claves): ?array
    {
        if (!is_array($contexto)) {
            return null;
        }

        $resultado = [];
        foreach ($claves as $clave) {
            if (is_numeric($contexto[$clave] ?? null)) {
                $resultado[$clave] = round((float) $contexto[$clave], 6);
            }
        }

        return $resultado ?: null;
    }

    private function frameTieneSenal(array $frame): bool
    {
        return !empty($frame['manos']) || !empty($frame['rostro']) || !empty($frame['cuerpo']);
    }

    private function mensajeCalidad(int $frames, int $duracion, float $calidad, bool $gestoCorporal): string
    {
        if ($frames < 10 && !$gestoCorporal) {
            return 'No se vieron las manos durante suficiente tiempo. Repita la grabacion dentro del recuadro.';
        }
        if ($duracion < 550) {
            return 'La grabacion fue demasiado corta. Realice la sena completa con calma.';
        }
        if ($duracion > 9000) {
            return 'La grabacion fue demasiado larga. Grabe solamente una sena o frase.';
        }
        if ($calidad < 0.50) {
            return 'La captura tuvo poca continuidad. Mejore la luz y mantenga manos, rostro y torso visibles.';
        }

        return 'Captura lista para entrenamiento.';
    }

    private function descriptorSecuencia(array $secuencia): array
    {
        $frames = $this->muestrear($secuencia, self::SAMPLE_FRAMES);
        $descriptor = [];
        foreach ($frames as $frame) {
            $descriptor = array_merge($descriptor, $this->descriptorFrameCompacto($frame));
        }

        return array_map(fn ($valor) => round($valor, 5), $descriptor);
    }

    private function descriptorFrameCompacto(array $frame): array
    {
        $resultado = [];
        $manos = $frame['manos'] ?? [];

        foreach ([0, 1] as $indiceMano) {
            $mano = $manos[$indiceMano] ?? null;
            if (!$mano) {
                $resultado = array_merge($resultado, array_fill(0, 39, 0));
                continue;
            }

            $normalizados = $this->normalizarMano($mano);
            foreach ([0, 4, 8, 12, 16, 20, 5, 9, 13, 17] as $indice) {
                $resultado[] = $normalizados[$indice]['x'];
                $resultado[] = $normalizados[$indice]['y'];
                $resultado[] = $normalizados[$indice]['z'];
            }

            foreach ([[4, 8], [8, 12], [12, 16], [16, 20], [8, 20], [4, 20], [0, 12], [5, 17], [0, 9]] as [$a, $b]) {
                $resultado[] = $this->distancia($mano[$a], $mano[$b]) / $this->escalaMano($mano);
            }
        }

        $resultado = array_merge($resultado, $this->contextoFrame($frame));

        return $resultado;
    }

    private function descriptorTrayectoria(array $secuencia): array
    {
        $frames = $this->muestrear($secuencia, self::SAMPLE_FRAMES);
        $resultado = [];
        $origenes = [];

        foreach ($frames as $frame) {
            $manos = $frame['manos'] ?? [];
            foreach ([0, 1] as $indice) {
                $centro = isset($manos[$indice]) ? $this->centroMano($manos[$indice]) : ['x' => 0, 'y' => 0, 'z' => 0];
                $origenes[$indice] ??= $centro;
                $escala = max(0.12, (float) ($frame['cuerpo']['ancho_hombros'] ?? 0.28));
                $resultado[] = ($centro['x'] - $origenes[$indice]['x']) / $escala;
                $resultado[] = ($centro['y'] - $origenes[$indice]['y']) / $escala;
                $resultado[] = ($centro['z'] - $origenes[$indice]['z']) / $escala;
            }
            $resultado[] = count($manos) / 2;
            $resultado[] = $this->separacionManos($manos);
            $resultado[] = (float) ($frame['rostro']['yaw'] ?? 0);
            $resultado[] = (float) ($frame['rostro']['pitch'] ?? 0);
            $resultado[] = (float) ($frame['rostro']['boca'] ?? 0);
        }

        return array_map(fn ($valor) => round($valor, 5), $resultado);
    }

    private function normalizarMano(array $mano): array
    {
        $muneca = $mano[0];
        $escala = $this->escalaMano($mano);
        $angulo = atan2($mano[9]['y'] - $muneca['y'], $mano[9]['x'] - $muneca['x']);
        $cos = cos(-$angulo);
        $sin = sin(-$angulo);
        $resultado = [];

        foreach ($mano as $punto) {
            $x = ($punto['x'] - $muneca['x']) / $escala;
            $y = ($punto['y'] - $muneca['y']) / $escala;
            $resultado[] = [
                'x' => ($x * $cos) - ($y * $sin),
                'y' => ($x * $sin) + ($y * $cos),
                'z' => ($punto['z'] - $muneca['z']) / $escala,
            ];
        }

        return $resultado;
    }

    private function distanciaDtw(array $actual, array $muestra): float
    {
        $actual = $this->agregarRelacionesSecuencia($this->muestrear($actual, 14));
        $muestra = $this->agregarRelacionesSecuencia($this->muestrear($muestra, 14));
        $n = count($actual);
        $m = count($muestra);
        if (!$n || !$m) {
            return 9;
        }

        $costos = array_fill(0, $n + 1, array_fill(0, $m + 1, INF));
        $pasos = array_fill(0, $n + 1, array_fill(0, $m + 1, PHP_INT_MAX));
        $costos[0][0] = 0;
        $pasos[0][0] = 0;

        for ($i = 1; $i <= $n; $i++) {
            for ($j = 1; $j <= $m; $j++) {
                $opciones = [
                    [$costos[$i - 1][$j], $pasos[$i - 1][$j]],
                    [$costos[$i][$j - 1], $pasos[$i][$j - 1]],
                    [$costos[$i - 1][$j - 1], $pasos[$i - 1][$j - 1]],
                ];
                usort($opciones, fn ($a, $b) => $a[0] <=> $b[0]);
                $costos[$i][$j] = $opciones[0][0] + $this->distanciaFrame($actual[$i - 1], $muestra[$j - 1]);
                $pasos[$i][$j] = $opciones[0][1] + 1;
            }
        }

        return $costos[$n][$m] / max(1, $pasos[$n][$m]);
    }

    private function distanciaFrame(array $a, array $b): float
    {
        $manosA = $a['manos'] ?? [];
        $manosB = $b['manos'] ?? [];
        if (!$manosA && !$manosB) {
            return $this->distanciaContexto($a, $b);
        }
        if (!$manosA || !$manosB) {
            return 0.85 + $this->distanciaContexto($a, $b) * 0.15;
        }

        $directa = $this->distanciaEmparejamientoManos($manosA, $manosB);
        if (count($manosA) === 2 && count($manosB) === 2) {
            $invertida = $this->distanciaEmparejamientoManos($manosA, array_reverse($manosB));
            $directa = min($directa, $invertida);
        }

        $penalizacionCantidad = abs(count($manosA) - count($manosB)) * 0.28;
        $relacion = abs($this->separacionManos($manosA) - $this->separacionManos($manosB)) * 1.4;
        $relacionCorporal = $this->distanciaRelacionesCorporales($a, $b);

        return ($directa * 0.64)
            + ($relacion * 0.12)
            + ($this->distanciaContexto($a, $b) * 0.08)
            + ($relacionCorporal * 0.16)
            + $penalizacionCantidad;
    }

    private function distanciaEmparejamientoManos(array $a, array $b): float
    {
        $cantidad = min(count($a), count($b));
        $suma = 0;
        for ($i = 0; $i < $cantidad; $i++) {
            $suma += $this->distanciaFormaMano($a[$i], $b[$i]);
        }

        return $suma / max(1, $cantidad);
    }

    private function distanciaFormaMano(array $a, array $b): float
    {
        $a = $this->normalizarMano($a);
        $b = $this->normalizarMano($b);
        $suma = 0;
        $pesos = [4 => 1.4, 8 => 1.8, 12 => 1.8, 16 => 1.7, 20 => 1.7];
        $pesoTotal = 0;

        for ($i = 0; $i < 21; $i++) {
            $peso = $pesos[$i] ?? 1.0;
            $suma += (
                (($a[$i]['x'] - $b[$i]['x']) ** 2)
                + (($a[$i]['y'] - $b[$i]['y']) ** 2)
                + ((($a[$i]['z'] - $b[$i]['z']) * 0.65) ** 2)
            ) * $peso;
            $pesoTotal += $peso;
        }

        return sqrt($suma / max(1, $pesoTotal));
    }

    private function distanciaContexto(array $a, array $b): float
    {
        $rostroA = $a['rostro'] ?? [];
        $rostroB = $b['rostro'] ?? [];
        $cuerpoA = $a['cuerpo'] ?? [];
        $cuerpoB = $b['cuerpo'] ?? [];
        $valores = [];

        foreach ([['yaw', 2.0], ['pitch', 2.0], ['boca', 1.0], ['cejas', 1.0]] as [$clave, $peso]) {
            if (isset($rostroA[$clave], $rostroB[$clave])) {
                $valores[] = abs($rostroA[$clave] - $rostroB[$clave]) * $peso;
            }
        }
        foreach ([['inclinacion', 2.0], ['hombro_y', 1.0]] as [$clave, $peso]) {
            if (isset($cuerpoA[$clave], $cuerpoB[$clave])) {
                $valores[] = abs($cuerpoA[$clave] - $cuerpoB[$clave]) * $peso;
            }
        }

        return $valores ? array_sum($valores) / count($valores) : 0;
    }

    private function distanciaRelacionesCorporales(array $a, array $b): float
    {
        $relacionesA = $a['relaciones_corporales'] ?? $this->vectoresRelacionCorporal($a);
        $relacionesB = $b['relaciones_corporales'] ?? $this->vectoresRelacionCorporal($b);
        if (!$relacionesA || !$relacionesB) {
            return 0;
        }

        $directa = $this->distanciaRelacionesEnOrden($relacionesA, $relacionesB);
        if (count($relacionesA) === 2 && count($relacionesB) === 2) {
            $invertida = $this->distanciaRelacionesEnOrden(
                $relacionesA,
                array_reverse($relacionesB)
            );
            return min($directa, $invertida);
        }

        return $directa;
    }

    private function distanciaRelacionesEnOrden(
        array $relacionesA,
        array $relacionesB
    ): float {
        $cantidad = min(count($relacionesA), count($relacionesB));
        $suma = 0.0;
        $comparaciones = 0;

        for ($indice = 0; $indice < $cantidad; $indice++) {
            $vectorA = $relacionesA[$indice];
            $vectorB = $relacionesB[$indice];
            $zonasComunes = array_intersect(array_keys($vectorA), array_keys($vectorB));

            foreach ($zonasComunes as $zona) {
                $suma += min(1.5, abs($vectorA[$zona] - $vectorB[$zona]));
                $comparaciones++;
            }
        }

        return $comparaciones ? $suma / $comparaciones : 0;
    }

    private function agregarRelacionesSecuencia(array $secuencia): array
    {
        foreach ($secuencia as &$frame) {
            if (!isset($frame['relaciones_corporales'])) {
                $frame['relaciones_corporales'] = $this->vectoresRelacionCorporal($frame);
            }
        }
        unset($frame);

        return $secuencia;
    }

    private function vectoresRelacionCorporal(array $frame): array
    {
        $mapa = $this->mapaZonasCorporales($frame);
        $resultado = [];
        foreach (($frame['manos'] ?? []) as $mano) {
            $resultado[] = $this->vectorRelacionCorporal($frame, $mano, $mapa);
        }

        return $resultado;
    }

    private function vectorRelacionCorporal(
        array $frame,
        array $mano,
        ?array $mapaCalculado = null
    ): array
    {
        $mapa = $mapaCalculado ?? $this->mapaZonasCorporales($frame);
        if (!$mapa['zonas']) {
            return [];
        }

        $indices = [0, 4, 8, 12, 16, 20, 5, 9, 13, 17];
        $resultado = [];
        foreach ($mapa['zonas'] as $nombre => $zona) {
            $distanciaMinima = INF;
            foreach ($indices as $indice) {
                $punto = $mano[$indice] ?? null;
                if (!$punto) {
                    continue;
                }

                $dx = ($punto['x'] ?? 0) - $zona['x'];
                $dy = ($punto['y'] ?? 0) - $zona['y'];
                $dz = (($punto['z'] ?? 0) - ($zona['z'] ?? 0)) * 0.20;
                $distancia = sqrt(($dx ** 2) + ($dy ** 2) + ($dz ** 2))
                    / $mapa['escala'];
                $distanciaMinima = min($distanciaMinima, $distancia);
            }

            if (is_finite($distanciaMinima)) {
                $resultado[$nombre] = min(3, $distanciaMinima);
            }
        }

        return $resultado;
    }

    private function mapaZonasCorporales(array $frame): array
    {
        $rostro = $frame['rostro'] ?? [];
        $cuerpo = $frame['cuerpo'] ?? [];
        $anchoHombros = max(0.0, (float) ($cuerpo['ancho_hombros'] ?? 0));
        $escalaRostro = max(
            0.0,
            (float) ($rostro['escala_rostro'] ?? 0),
            $anchoHombros > 0 ? $anchoHombros * 0.42 : 0
        );
        $escala = max(0.12, $anchoHombros, $escalaRostro * 1.9);
        $zonas = [];

        if (isset($rostro['x'], $rostro['y'])) {
            $x = (float) $rostro['x'];
            $y = (float) $rostro['y'];
            $z = (float) ($rostro['z'] ?? 0);
            $radio = max(0.055, $escalaRostro);
            $zonas['boca'] = [
                'x' => (float) ($rostro['boca_x'] ?? $x),
                'y' => (float) ($rostro['boca_y'] ?? ($y + ($radio * 0.30))),
                'z' => (float) ($rostro['boca_z'] ?? $z),
            ];
            $zonas['frente'] = [
                'x' => (float) ($rostro['frente_x'] ?? $x),
                'y' => (float) ($rostro['frente_y'] ?? ($y - ($radio * 0.42))),
                'z' => (float) ($rostro['frente_z'] ?? $z),
            ];
            $zonas['cachete_izq'] = [
                'x' => (float) ($rostro['cachete_izq_x'] ?? ($x - ($radio * 0.48))),
                'y' => (float) ($rostro['cachete_izq_y'] ?? ($y + ($radio * 0.08))),
                'z' => (float) ($rostro['cachete_izq_z'] ?? $z),
            ];
            $zonas['cachete_der'] = [
                'x' => (float) ($rostro['cachete_der_x'] ?? ($x + ($radio * 0.48))),
                'y' => (float) ($rostro['cachete_der_y'] ?? ($y + ($radio * 0.08))),
                'z' => (float) ($rostro['cachete_der_z'] ?? $z),
            ];
        }

        if (isset($cuerpo['hombro_x'], $cuerpo['hombro_y'])) {
            $x = (float) $cuerpo['hombro_x'];
            $y = (float) $cuerpo['hombro_y'];
            $mitad = max(0.06, $anchoHombros / 2);
            $zonas['hombro_izq'] = [
                'x' => (float) ($cuerpo['hombro_izq_x'] ?? ($x - $mitad)),
                'y' => (float) ($cuerpo['hombro_izq_y'] ?? $y),
                'z' => (float) ($cuerpo['hombro_izq_z'] ?? 0),
            ];
            $zonas['hombro_der'] = [
                'x' => (float) ($cuerpo['hombro_der_x'] ?? ($x + $mitad)),
                'y' => (float) ($cuerpo['hombro_der_y'] ?? $y),
                'z' => (float) ($cuerpo['hombro_der_z'] ?? 0),
            ];
            $zonas['pecho'] = [
                'x' => (float) ($cuerpo['pecho_x'] ?? $x),
                'y' => (float) ($cuerpo['pecho_y'] ?? ($y + (max(0.12, $anchoHombros) * 0.55))),
                'z' => (float) ($cuerpo['pecho_z'] ?? 0),
            ];
        }

        return ['zonas' => $zonas, 'escala' => $escala];
    }

    private function distanciaExtremos(array $a, array $b): float
    {
        if (!$a || !$b) {
            return 9;
        }

        return (
            $this->distanciaFrame($a[0], $b[0])
            + $this->distanciaFrame($a[count($a) - 1], $b[count($b) - 1])
        ) / 2;
    }

    private function penalizacionCompatibilidad(array $actual, array $muestra): float
    {
        if (!$muestra) {
            return 0.25;
        }

        $penalizacion = 0.0;
        $manosActual = (int) ($actual['manos_max'] ?? 0);
        $manosMuestra = (int) ($muestra['manos_max'] ?? 0);
        if ($manosActual !== $manosMuestra) {
            $penalizacion += abs($manosActual - $manosMuestra) * 0.16;
        }
        if (($actual['tipo_movimiento'] ?? '') !== ($muestra['tipo_movimiento'] ?? '')) {
            $penalizacion += 0.06;
        }

        $duracionA = max(1, (int) ($actual['duracion_ms'] ?? 1));
        $duracionB = max(1, (int) ($muestra['duracion_ms'] ?? 1));
        $penalizacion += min(0.10, abs(log($duracionA / $duracionB)) * 0.05);

        return $penalizacion;
    }

    private function movimientoManos(array $secuencia): float
    {
        $anteriores = [];
        $cambios = [];
        foreach ($secuencia as $frame) {
            foreach (($frame['manos'] ?? []) as $indice => $mano) {
                $centro = $this->centroMano($mano);
                if (isset($anteriores[$indice])) {
                    $desplazamiento = $this->distancia($centro, $anteriores[$indice]['centro'])
                        / max(0.025, $this->escalaMano($mano));
                    $forma = $this->distanciaFormaMano($mano, $anteriores[$indice]['mano']);
                    $cambios[] = min(1, ($desplazamiento * 0.42) + ($forma * 1.25));
                }
                $anteriores[$indice] = ['centro' => $centro, 'mano' => $mano];
            }
        }

        if (!$cambios) {
            return 0;
        }

        sort($cambios);
        $inicio = (int) floor(count($cambios) * 0.35);
        $relevantes = array_slice($cambios, $inicio);

        return min(1, (array_sum($relevantes) / max(1, count($relevantes))) * 1.8);
    }

    private function movimientoRostro(array $secuencia): float
    {
        $anterior = null;
        $cambios = [];
        foreach ($secuencia as $frame) {
            $rostro = $frame['rostro'] ?? null;
            if (!$rostro) {
                continue;
            }
            if ($anterior) {
                $cambios[] = min(1,
                    abs(($rostro['yaw'] ?? 0) - ($anterior['yaw'] ?? 0)) * 6
                    + abs(($rostro['pitch'] ?? 0) - ($anterior['pitch'] ?? 0)) * 6
                    + abs(($rostro['boca'] ?? 0) - ($anterior['boca'] ?? 0)) * 2
                );
            }
            $anterior = $rostro;
        }

        return $cambios ? min(1, (array_sum($cambios) / count($cambios)) * 2.2) : 0;
    }

    private function contextoFrame(array $frame): array
    {
        $manos = $frame['manos'] ?? [];
        $rostro = $frame['rostro'] ?? [];
        $cuerpo = $frame['cuerpo'] ?? [];

        return [
            count($manos) / 2,
            $this->separacionManos($manos),
            (float) ($rostro['yaw'] ?? 0),
            (float) ($rostro['pitch'] ?? 0),
            (float) ($rostro['boca'] ?? 0),
            (float) ($rostro['cejas'] ?? 0),
            (float) ($cuerpo['inclinacion'] ?? 0),
            (float) ($cuerpo['ancho_hombros'] ?? 0),
        ];
    }

    private function centroMano(array $mano): array
    {
        $indices = [0, 5, 9, 13, 17];
        $centro = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];
        foreach ($indices as $indice) {
            $centro['x'] += $mano[$indice]['x'];
            $centro['y'] += $mano[$indice]['y'];
            $centro['z'] += $mano[$indice]['z'];
        }
        foreach ($centro as $clave => $valor) {
            $centro[$clave] = $valor / count($indices);
        }

        return $centro;
    }

    private function separacionManos(array $manos): float
    {
        if (count($manos) < 2) {
            return 0;
        }

        $escala = max(0.02, ($this->escalaMano($manos[0]) + $this->escalaMano($manos[1])) / 2);

        return $this->distancia($this->centroMano($manos[0]), $this->centroMano($manos[1])) / $escala;
    }

    private function escalaMano(array $mano): float
    {
        return max(0.0001, $this->distancia($mano[0], $mano[9]));
    }

    private function distancia(array $a, array $b): float
    {
        return sqrt(
            (($a['x'] ?? 0) - ($b['x'] ?? 0)) ** 2
            + (($a['y'] ?? 0) - ($b['y'] ?? 0)) ** 2
            + (($a['z'] ?? 0) - ($b['z'] ?? 0)) ** 2
        );
    }

    private function distanciaVector(array $a, array $b): float
    {
        $cantidad = min(count($a), count($b));
        if ($cantidad === 0) {
            return 9;
        }

        $suma = 0;
        for ($i = 0; $i < $cantidad; $i++) {
            $suma += ((float) $a[$i] - (float) $b[$i]) ** 2;
        }
        $diferenciaTamano = abs(count($a) - count($b)) / max(1, count($a), count($b));

        return sqrt($suma / $cantidad) + ($diferenciaTamano * 0.3);
    }

    private function muestrear(array $items, int $cantidad): array
    {
        $total = count($items);
        if ($total <= $cantidad) {
            if ($total === 0) {
                return [];
            }
            while (count($items) < $cantidad) {
                $items[] = $items[count($items) - 1];
            }

            return $items;
        }

        $resultado = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $indice = (int) round($i * ($total - 1) / max(1, $cantidad - 1));
            $resultado[] = $items[$indice];
        }

        return $resultado;
    }

    private function limitar(float $valor): float
    {
        return max(0, min(1, $valor));
    }
}
