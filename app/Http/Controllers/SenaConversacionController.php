<?php

namespace App\Http\Controllers;

use App\Services\SenaRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SenaConversacionController extends Controller
{
    private SenaRecognitionService $recognizer;

    public function __construct(SenaRecognitionService $recognizer)
    {
        $this->recognizer = $recognizer;
    }

    public function reconocer(Request $request)
    {
        try {
            $modoEntrenamiento = $request->boolean('modo_entrenamiento');
            $manos = $this->normalizarManos($request->input('manos'), $request->input('puntos'));
            $secuencia = $this->recognizer->prepararSecuencia($request->input('secuencia'), $manos);
            $analisis = $this->recognizer->analizarSecuencia($secuencia);

            if (!$analisis['utilizable']) {
                return response()->json([
                    'success' => true,
                    'codigo' => null,
                    'texto' => $analisis['mensaje'],
                    'estado' => 'capturando',
                    'calidad' => $analisis['calidad'],
                ]);
            }

            $revisionModelo = $this->revisionModelo();
            $muestras = collect();
            $respuestaPython = $this->clasificarConPython(
                $secuencia,
                $muestras,
                $revisionModelo
            );

            if ($respuestaPython['requiere_modelo'] ?? false) {
                $muestras = $this->cargarMuestrasModelo();
                $respuestaPython = $this->clasificarConPython(
                    $secuencia,
                    $muestras,
                    $revisionModelo,
                    true
                );
            }

            if ($respuestaPython['disponible'] ?? false) {
                // Una respuesta valida sin prediccion significa "no reconocida".
                // No se repite el calculo completo en PHP.
                $prediccion = $respuestaPython['prediccion'] ?? null;
            } else {
                if ($muestras->isEmpty()) {
                    $muestras = $this->cargarMuestrasModelo();
                }
                $prediccion = $this->recognizer->clasificar($secuencia, $muestras);
            }

            $hayEntrenamiento = !$muestras->isEmpty()
                || (int) ($respuestaPython['muestras_modelo'] ?? 0) > 0;

            if (!$prediccion) {
                return response()->json([
                    'success' => true,
                    'codigo' => null,
                    'texto' => $hayEntrenamiento
                        ? 'Sena no reconocida con suficiente seguridad.'
                        : 'Todavia no hay senas entrenadas con el modelo actual.',
                    'estado' => $hayEntrenamiento ? 'incierto' : 'sin_entrenamiento',
                    'calidad' => $analisis['calidad'],
                ]);
            }

            if (!($prediccion['aceptada'] ?? false)) {
                $respuesta = [
                    'success' => true,
                    'codigo' => null,
                    'texto' => ($prediccion['es_negativa'] ?? false)
                        ? 'Movimiento aprendido como ninguna sena.'
                        : 'Sena no reconocida con suficiente seguridad.',
                    'estado' => ($prediccion['es_negativa'] ?? false)
                        ? 'sin_sena'
                        : 'incierto',
                    'calidad' => $analisis['calidad'],
                    'diagnostico' => $prediccion['diagnostico'],
                ];

                // En consulta solo se muestran candidatos razonables. Durante el
                // entrenamiento, el usuario necesita ver incluso la mejor similitud baja.
                if (
                    !($prediccion['es_negativa'] ?? false) &&
                    ($modoEntrenamiento || ($prediccion['confianza'] ?? 0) >= 0.66)
                ) {
                    $respuesta['candidato'] = [
                        'codigo' => $prediccion['codigo_candidato'] ?? null,
                        'texto' => $prediccion['texto'],
                        'confianza' => $prediccion['confianza'],
                    ];
                }

                return response()->json($respuesta);
            }

            return response()->json([
                'success' => true,
                'tipo' => 'conversacion',
                'origen' => 'aprendizaje',
                'codigo' => $prediccion['codigo'],
                'texto' => $prediccion['texto'],
                'confianza' => $prediccion['confianza'],
                'alternativas' => $prediccion['alternativas'],
                'diagnostico' => $prediccion['diagnostico'],
                'calidad' => $analisis['calidad'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo analizar la sena en este momento.',
            ], 500);
        }
    }

    public function guardarMuestra(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:100'],
            'texto' => ['nullable', 'string', 'max:180'],
            'puntos' => ['nullable', 'array'],
            'manos' => ['nullable', 'array'],
            'secuencia' => ['required', 'array', 'min:8', 'max:220'],
            'tipo_aprendizaje' => ['nullable', 'string', 'in:entrenamiento,confirmada,corregida,ninguna'],
            'codigo_rechazado' => ['nullable', 'string', 'max:100'],
            'confianza_predicha' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $manos = $this->normalizarManos($data['manos'] ?? null, $data['puntos'] ?? null);
        $secuencia = $this->recognizer->prepararSecuencia($data['secuencia'], $manos);
        $analisis = $this->recognizer->analizarSecuencia($secuencia);
        $texto = trim((string) ($data['texto'] ?? ''));
        $codigoSolicitado = trim((string) ($data['codigo'] ?? ''));
        $tipoAprendizaje = (string) ($data['tipo_aprendizaje'] ?? 'entrenamiento');
        $codigoRechazado = trim((string) ($data['codigo_rechazado'] ?? ''));
        $confianzaPredicha = isset($data['confianza_predicha'])
            ? (float) $data['confianza_predicha']
            : null;
        $esNegativa = $codigoSolicitado === SenaRecognitionService::NEGATIVE_CODE;
        if ($esNegativa) {
            $tipoAprendizaje = 'ninguna';
        }
        $negativaUtilizable = $esNegativa
            && ($analisis['frames_visibles'] ?? 0) >= 7
            && ($analisis['duracion_ms'] ?? 0) >= 280
            && ($analisis['calidad'] ?? 0) >= 0.50;

        if (!$analisis['valida_para_entrenar'] && !$negativaUtilizable) {
            return response()->json([
                'success' => false,
                'message' => $analisis['mensaje_entrenamiento'],
                'calidad' => $analisis['calidad'],
            ], 422);
        }

        if ($texto === '' && $codigoSolicitado === '') {
            return response()->json([
                'success' => false,
                'message' => 'Escriba la frase que representa la sena.',
            ], 422);
        }

        $resultado = DB::transaction(function () use (
            $texto,
            $codigoSolicitado,
            $secuencia,
            $analisis,
            $tipoAprendizaje,
            $codigoRechazado,
            $confianzaPredicha,
        ) {
            $etiqueta = $this->resolverEtiqueta($texto, $codigoSolicitado);
            $features = $this->recognizer->crearFeatures($secuencia, $analisis);
            $aprendizaje = [
                'rol' => $tipoAprendizaje,
                'confianza_predicha' => $confianzaPredicha,
                'codigo_rechazado' => $codigoRechazado !== '' ? $codigoRechazado : null,
            ];
            $features['aprendizaje'] = $aprendizaje;
            $metricas = array_merge($analisis, ['aprendizaje' => $aprendizaje]);

            DB::table('sena_conversacion_muestras')->insert([
                'etiqueta_id' => $etiqueta->id,
                'codigo' => $etiqueta->codigo,
                'texto' => $etiqueta->texto,
                'puntos' => json_encode($secuencia, JSON_UNESCAPED_UNICODE),
                'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                'version_modelo' => SenaRecognitionService::MODEL_VERSION,
                'calidad' => $analisis['calidad'],
                'duracion_ms' => $analisis['duracion_ms'],
                'cantidad_frames' => $analisis['frames_visibles'],
                'manos_max' => $analisis['manos_max'],
                'tipo_movimiento' => $analisis['tipo_movimiento'],
                'metricas' => json_encode($metricas, JSON_UNESCAPED_UNICODE),
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Una correccion enseña dos cosas: cual es la etiqueta correcta y
            // que esa misma secuencia no debe volver a clasificarse como la
            // etiqueta equivocada. La contra-muestra vive en la misma tabla,
            // pero Python la usa solo como evidencia de rechazo.
            if ($codigoRechazado !== '' && $codigoRechazado !== $etiqueta->codigo) {
                $etiquetaRechazada = DB::table('sena_conversacion_etiquetas')
                    ->where('codigo', $codigoRechazado)
                    ->where('activa', true)
                    ->first();

                if ($etiquetaRechazada) {
                    $featuresRechazo = $features;
                    $featuresRechazo['aprendizaje'] = [
                        'rol' => 'rechazo',
                        'codigo_correcto' => $etiqueta->codigo,
                        'codigo_rechazado' => $codigoRechazado,
                    ];

                    DB::table('sena_conversacion_muestras')->insert([
                        'etiqueta_id' => $etiquetaRechazada->id,
                        'codigo' => $etiquetaRechazada->codigo,
                        'texto' => $etiquetaRechazada->texto,
                        'puntos' => json_encode($secuencia, JSON_UNESCAPED_UNICODE),
                        'features' => json_encode($featuresRechazo, JSON_UNESCAPED_UNICODE),
                        'version_modelo' => SenaRecognitionService::MODEL_VERSION,
                        'calidad' => $analisis['calidad'],
                        'duracion_ms' => $analisis['duracion_ms'],
                        'cantidad_frames' => $analisis['frames_visibles'],
                        'manos_max' => $analisis['manos_max'],
                        'tipo_movimiento' => $analisis['tipo_movimiento'],
                        'metricas' => json_encode([
                            ...$analisis,
                            'aprendizaje' => $featuresRechazo['aprendizaje'],
                        ], JSON_UNESCAPED_UNICODE),
                        'activa' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $total = DB::table('sena_conversacion_muestras')
                ->where('etiqueta_id', $etiqueta->id)
                ->where('version_modelo', SenaRecognitionService::MODEL_VERSION)
                ->where('activa', true)
                ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(features, '$.aprendizaje.rol')), '') <> 'rechazo'")
                ->count();

            DB::table('sena_conversacion_etiquetas')
                ->where('id', $etiqueta->id)
                ->update([
                    'muestras_validas' => $total,
                    'updated_at' => now(),
                ]);

            return [
                'codigo' => $etiqueta->codigo,
                'texto' => $etiqueta->texto,
                'total' => $total,
            ];
        });

        $this->limpiarCache();

        return response()->json([
            'success' => true,
            'message' => 'Muestra guardada correctamente.',
            ...$resultado,
            'calidad' => $analisis['calidad'],
            'tipo_movimiento' => $analisis['tipo_movimiento'],
            'recomendadas_restantes' => max(
                0,
                SenaRecognitionService::MINIMUM_SAMPLES_PER_PHRASE - $resultado['total']
            ),
        ]);
    }

    public function listarFrases(Request $request)
    {
        $busqueda = $this->normalizarTexto((string) $request->query('q', ''));

        $frases = DB::table('sena_conversacion_etiquetas as e')
            ->leftJoin('sena_conversacion_muestras as m', function ($join) {
                $join->on('m.etiqueta_id', '=', 'e.id')
                    ->where('m.activa', true)
                    ->where('m.version_modelo', SenaRecognitionService::MODEL_VERSION);
            })
            ->where('e.activa', true)
            ->where('e.codigo', '!=', SenaRecognitionService::NEGATIVE_CODE)
            ->when($busqueda !== '', fn ($query) => $query->where('e.texto_normalizado', 'like', '%' . $busqueda . '%'))
            ->groupBy('e.id', 'e.codigo', 'e.texto', 'e.updated_at')
            ->select('e.codigo', 'e.texto', DB::raw('COUNT(m.id) as total'), 'e.updated_at as ultima_muestra')
            ->orderByDesc('e.updated_at')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'version_modelo' => SenaRecognitionService::MODEL_VERSION,
            'frases' => $frases,
        ]);
    }

    public function eliminarFrase(string $codigo)
    {
        $etiqueta = DB::table('sena_conversacion_etiquetas')->where('codigo', $codigo)->first();

        if (!$etiqueta) {
            return response()->json(['success' => false, 'message' => 'La frase no existe.'], 404);
        }

        DB::transaction(function () use ($etiqueta) {
            DB::table('sena_conversacion_muestras')->where('etiqueta_id', $etiqueta->id)->delete();
            DB::table('sena_conversacion_etiquetas')->where('id', $etiqueta->id)->delete();
        });

        $this->limpiarCache();

        return response()->json(['success' => true, 'message' => 'Frase y muestras eliminadas.']);
    }

    private function resolverEtiqueta(string $texto, string $codigoSolicitado): object
    {
        if ($codigoSolicitado !== '') {
            $existente = DB::table('sena_conversacion_etiquetas')
                ->where('codigo', $codigoSolicitado)
                ->lockForUpdate()
                ->first();

            if ($existente) {
                return $existente;
            }

            if ($codigoSolicitado === SenaRecognitionService::NEGATIVE_CODE) {
                $id = DB::table('sena_conversacion_etiquetas')->insertGetId([
                    'codigo' => SenaRecognitionService::NEGATIVE_CODE,
                    'texto' => 'Ninguna sena',
                    'texto_normalizado' => 'ninguna sena',
                    'activa' => true,
                    'muestras_validas' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return DB::table('sena_conversacion_etiquetas')->where('id', $id)->first();
            }
        }

        $normalizado = $this->normalizarTexto($texto);
        if ($normalizado === '') {
            abort(422, 'La frase no contiene texto valido.');
        }

        $existente = DB::table('sena_conversacion_etiquetas')
            ->where('texto_normalizado', $normalizado)
            ->lockForUpdate()
            ->first();

        if ($existente) {
            return $existente;
        }

        $codigo = $this->codigoPersonalizado($normalizado);
        $id = DB::table('sena_conversacion_etiquetas')->insertGetId([
            'codigo' => $codigo,
            'texto' => $texto,
            'texto_normalizado' => $normalizado,
            'activa' => true,
            'muestras_validas' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('sena_conversacion_etiquetas')->where('id', $id)->first();
    }

    private function normalizarManos(mixed $manos, mixed $puntos): array
    {
        if (is_array($manos)) {
            $validas = array_values(array_filter(
                $manos,
                fn ($mano) => is_array($mano) && count($mano) >= 21
            ));

            if ($validas) {
                return array_slice($validas, 0, 2);
            }
        }

        return is_array($puntos) && count($puntos) >= 21 ? [$puntos] : [];
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = Str::ascii(Str::lower(trim($texto)));
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?: '';

        return trim(preg_replace('/\s+/', ' ', $texto) ?: '');
    }

    private function codigoPersonalizado(string $textoNormalizado): string
    {
        $base = trim(preg_replace('/[^a-z0-9]+/', '_', $textoNormalizado) ?: 'frase', '_');

        return substr($base, 0, 55) . '_' . substr(sha1($textoNormalizado), 0, 10);
    }

    private function limpiarCache(): void
    {
        Cache::forget('sena_conversacion_muestras_' . SenaRecognitionService::MODEL_VERSION);
        Cache::forget('sena_conversacion_muestras_aprendidas');
    }

    private function cargarMuestrasModelo()
    {
        // Python conserva este diccionario en memoria y Laravel solo vuelve a
        // enviarlo cuando cambia el entrenamiento. Se toman referencias
        // distribuidas en el historial para no olvidar todo salvo las ultimas.
        $etiquetaIds = DB::table('sena_conversacion_etiquetas')
            ->where('activa', true)
            ->orderByDesc('updated_at')
            ->limit(64)
            ->pluck('id')
            ->all();

        $etiquetaNegativaId = DB::table('sena_conversacion_etiquetas')
            ->where('codigo', SenaRecognitionService::NEGATIVE_CODE)
            ->where('activa', true)
            ->value('id');

        if ($etiquetaNegativaId && !in_array($etiquetaNegativaId, $etiquetaIds, true)) {
            $etiquetaIds[] = $etiquetaNegativaId;
        }

        $idsPositivos = collect($etiquetaIds)
            ->flatMap(function ($etiquetaId) use ($etiquetaNegativaId) {
                $maximo = $etiquetaId === $etiquetaNegativaId ? 32 : 16;
                $disponibles = DB::table('sena_conversacion_muestras')
                    ->where('etiqueta_id', $etiquetaId)
                    ->where('activa', true)
                    ->where('version_modelo', SenaRecognitionService::MODEL_VERSION)
                    ->where('calidad', '>=', SenaRecognitionService::MINIMUM_TRAINING_QUALITY)
                    ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(features, '$.aprendizaje.rol')), '') <> 'rechazo'")
                    ->orderByDesc('id')
                    ->limit(64)
                    ->pluck('id');

                return $this->seleccionarIdsDistribuidos($disponibles, $maximo);
            })
            ->unique()
            ->values();

        $idsRechazo = collect($etiquetaIds)
            ->flatMap(function ($etiquetaId) {
                return DB::table('sena_conversacion_muestras')
                    ->where('etiqueta_id', $etiquetaId)
                    ->where('activa', true)
                    ->where('version_modelo', SenaRecognitionService::MODEL_VERSION)
                    ->where('calidad', '>=', SenaRecognitionService::MINIMUM_TRAINING_QUALITY)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(features, '$.aprendizaje.rol')) = 'rechazo'")
                    ->orderByDesc('id')
                    ->limit(6)
                    ->pluck('id');
            })
            ->unique()
            ->values();

        $idsSeleccionados = $idsPositivos
            ->merge($idsRechazo)
            ->unique()
            ->values();

        return DB::table('sena_conversacion_muestras as m')
            ->join('sena_conversacion_etiquetas as e', 'e.id', '=', 'm.etiqueta_id')
            ->where('e.activa', true)
            ->whereIn('m.id', $idsSeleccionados)
            ->select(
                'm.id',
                'm.codigo',
                'm.puntos',
                'm.features',
                'm.calidad',
                'm.duracion_ms',
                'm.manos_max',
                'm.tipo_movimiento',
                'e.texto',
                'e.muestras_validas as muestras_totales'
            )
            ->limit(320)
            ->get();
    }

    private function seleccionarIdsDistribuidos($ids, int $maximo)
    {
        $ids = collect($ids)->filter()->values();
        $total = $ids->count();
        if ($total <= $maximo || $maximo <= 1) {
            return $ids->take(max(1, $maximo));
        }

        $seleccionados = [];
        for ($indice = 0; $indice < $maximo; $indice++) {
            $posicion = (int) round($indice * ($total - 1) / ($maximo - 1));
            $seleccionados[] = $ids[$posicion];
        }

        return collect($seleccionados)->unique()->values();
    }

    private function revisionModelo(): string
    {
        $estado = DB::table('sena_conversacion_etiquetas')
            ->where('activa', true)
            ->selectRaw('COUNT(*) as etiquetas, COALESCE(SUM(muestras_validas), 0) as muestras, MAX(updated_at) as actualizado')
            ->first();

        return sha1(implode(':', [
            SenaRecognitionService::MODEL_VERSION,
            (int) ($estado->etiquetas ?? 0),
            (int) ($estado->muestras ?? 0),
            (string) ($estado->actualizado ?? ''),
        ]));
    }

    private function clasificarConPython(
        array $secuencia,
        $muestras,
        string $revisionModelo,
        bool $sincronizarModelo = false
    ): ?array
    {
        $url = rtrim((string) env('SENAS_PYTHON_URL', 'http://127.0.0.1:5055'), '/');

        if ($url === '') {
            return null;
        }

        try {
            // Python siempre reduce la secuencia antes de comparar. Enviarla ya
            // compactada evita transportar decenas de cuadros repetidos por cada
            // muestra sin perder inicio, recorrido ni postura final del gesto.
            $secuenciaPython = $this->compactarSecuencia($secuencia, 14);
            $muestrasPython = $muestras
                ->map(fn ($muestra) => $this->prepararMuestraParaPython($muestra))
                ->values()
                ->all();

            $respuesta = Http::connectTimeout(1)
                ->timeout(4)
                ->acceptJson()
                ->post($url . '/recognize', [
                    'secuencia' => $secuenciaPython,
                    'muestras' => $muestrasPython,
                    'revision_modelo' => $revisionModelo,
                    'actualizar_modelo' => $sincronizarModelo,
                ]);

            if ($respuesta->status() === 409 && $respuesta->json('requiere_modelo')) {
                return [
                    'disponible' => true,
                    'requiere_modelo' => true,
                    'prediccion' => null,
                    'muestras_modelo' => 0,
                ];
            }

            if (!$respuesta->ok()) {
                return null;
            }

            $data = $respuesta->json();
            if (!$sincronizarModelo && !array_key_exists('muestras_modelo', $data)) {
                // Compatibilidad temporal con un proceso Python anterior que
                // todavia no mantiene el diccionario en memoria.
                return [
                    'disponible' => true,
                    'requiere_modelo' => true,
                    'prediccion' => null,
                    'muestras_modelo' => 0,
                ];
            }

            $prediccion = $data['prediccion'] ?? null;
            if (is_array($prediccion)) {
                $prediccion['diagnostico']['motor'] = 'python';
            } else {
                $prediccion = null;
            }

            return [
                'disponible' => true,
                'requiere_modelo' => false,
                'prediccion' => $prediccion,
                'muestras_modelo' => (int) ($data['muestras_modelo'] ?? $muestras->count()),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function prepararMuestraParaPython(object $muestra): array
    {
        $puntos = json_decode((string) ($muestra->puntos ?? '[]'), true);

        return [
            'id' => $muestra->id,
            'codigo' => $muestra->codigo,
            'texto' => $muestra->texto,
            'puntos' => $this->compactarSecuencia(is_array($puntos) ? $puntos : [], 14),
            'features' => $muestra->features,
            'calidad' => $muestra->calidad,
            'duracion_ms' => $muestra->duracion_ms,
            'manos_max' => $muestra->manos_max,
            'tipo_movimiento' => $muestra->tipo_movimiento,
            'muestras_totales' => $muestra->muestras_totales,
        ];
    }

    private function compactarSecuencia(array $secuencia, int $maximoFrames): array
    {
        $frames = array_values(array_filter($secuencia, 'is_array'));
        $total = count($frames);

        if ($total <= $maximoFrames || $maximoFrames < 2) {
            return $frames;
        }

        $compacta = [];
        for ($indice = 0; $indice < $maximoFrames; $indice++) {
            $posicion = (int) round($indice * ($total - 1) / ($maximoFrames - 1));
            $compacta[] = $frames[$posicion];
        }

        return $compacta;
    }
}
