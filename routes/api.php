<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User; 


Route::post('/validar-gesto', function (Request $request) {
    return response()->json([
        'mensaje' => 'Amo lo preciosa que es tu carita',
        'status' => 'success'
    ]);
});

Route::get('/validar-gesto', function () {
    return "La API esta viva, pero debes usar POST";
});


Route::post('/rostro/registrar', function (Request $request) {
    // 1. Validamos que el vector esté presente
    if (!$request->has('vector')) {
        return response()->json(['error' => 'No se recibió el vector facial'], 400);
    }

    $user = User::find(1); 
    
    if (!$user) {
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }

    // 2. Guardamos el vector
    // IMPORTANTE: Asegúrate de que face_vector esté en el $fillable del modelo User
    $user->face_vector = json_encode($request->vector);
    $user->save();

    return response()->json(['mensaje' => '¡Rostro de ' . $user->name . ' guardado correctamente!']);
});

Route::post('/rostro/login', function (Request $request) {
    $vectorActual = $request->vector_actual;

    if (!$vectorActual) {
        return response()->json(['mensaje' => 'Falta el vector actual'], 400);
    }

    $usuarios = User::whereNotNull('face_vector')->get();
    $distanciaMasCercana = 999; // Para debug

    foreach ($usuarios as $user) {
        $vectorGuardado = json_decode($user->face_vector);
        
        $distancia = 0;
        // Calculamos la Distancia Euclidiana
        for ($i = 0; $i < count($vectorActual); $i++) {
            $distancia += pow($vectorActual[$i] - $vectorGuardado[$i], 2);
        }
        $distancia = sqrt($distancia);
        
        if ($distancia < $distanciaMasCercana) $distanciaMasCercana = $distancia;

        // Umbral de 0.6 (Si es menor, es la misma persona)
        if ($distancia < 0.6) {
            return response()->json([
                'mensaje' => 'Acceso Concedido',
                'usuario' => $user->name,
                'distancia' => $distancia
            ]);
        }
    }

    return response()->json([
        'mensaje' => 'Usuario no reconocido', 
        'distancia_minima' => $distanciaMasCercana
    ], 401);
});