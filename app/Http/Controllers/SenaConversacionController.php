<?php

namespace App\Http\Controllers;

use App\Services\SenaRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            $idsClasificados = DB::table('sena_conversacion_muestras as m')
                ->where('m.activa', true)
                ->where('m.version_modelo', SenaRecognitionService::MODEL_VERSION)
                ->where('m.calidad', '>=', SenaRecognitionService::MINIMUM_TRAINING_QUALITY)
                ->select(
                    'm.id',
                    'm.etiqueta_id',
                    DB::raw('ROW_NUMBER() OVER (PARTITION BY m.etiqueta_id ORDER BY m.id DESC) as orden_clase')
                );
            $idsSeleccionados = DB::query()
                ->fromSub($idsClasificados, 'ids_clasificados')
                ->where('orden_clase', '<=', 8)
                ->select('id');

            // Los puntos completos son demasiado grandes para FileStore. Se consultan
            // directamente despues de ordenar solamente sus identificadores livianos.
            $muestras = DB::table('sena_conversacion_muestras as m')
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
                    'e.texto'
                )
                ->limit(96)
                ->get();

            $prediccion = $this->recognizer->clasificar($secuencia, $muestras);

            if (!$prediccion) {
                return response()->json([
                    'success' => true,
                    'codigo' => null,
                    'texto' => $muestras->isEmpty()
                        ? 'Todavia no hay senas entrenadas con el modelo actual.'
                        : 'Sena no reconocida con suficiente seguridad.',
                    'estado' => $muestras->isEmpty() ? 'sin_entrenamiento' : 'incierto',
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
        ]);

        $manos = $this->normalizarManos($data['manos'] ?? null, $data['puntos'] ?? null);
        $secuencia = $this->recognizer->prepararSecuencia($data['secuencia'], $manos);
        $analisis = $this->recognizer->analizarSecuencia($secuencia);
        $texto = trim((string) ($data['texto'] ?? ''));
        $codigoSolicitado = trim((string) ($data['codigo'] ?? ''));
        $esNegativa = $codigoSolicitado === SenaRecognitionService::NEGATIVE_CODE;
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

        $resultado = DB::transaction(function () use ($texto, $codigoSolicitado, $secuencia, $analisis) {
            $etiqueta = $this->resolverEtiqueta($texto, $codigoSolicitado);
            $features = $this->recognizer->crearFeatures($secuencia, $analisis);

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
                'metricas' => json_encode($analisis, JSON_UNESCAPED_UNICODE),
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $total = DB::table('sena_conversacion_muestras')
                ->where('etiqueta_id', $etiqueta->id)
                ->where('version_modelo', SenaRecognitionService::MODEL_VERSION)
                ->where('activa', true)
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
}
