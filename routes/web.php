<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// Importaciones de Modelos
use App\Models\User;
use App\Models\Doctor;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Vistas Principales ---

Route::get('/', function () {
    return view('auth.login');
})->name('web.login');

Route::get('/registro', function () {
    return view('auth.registro');
})->name('registro');

Route::get('/home', function () {
    return view('paginas.home.inicio');
})->name('home');

Route::get('/reservar-cita', function () {
    try {
        $doctores = \App\Models\Doctor::where('esta_activo', true)->get();
        return view('paginas.citas.citas', compact('doctores'));
    } catch (\Exception $e) {
        return "Error en la base de datos: " . $e->getMessage();
    }
})->name('cita.reserva');

Route::get('/cita-confirmada', function () {
    return view('paginas.confirmacion-citas.confirmacion');
})->name('cita.confirmada');

Route::get('/seleccion-manual', function () {
    return view('paginas.off-señas.off-señas');
})->name('especialidad.manual');



Route::get('/reconocimiento-senas', function () {
    return view('paginas.on-señas.on-señas');
})->name('señas');



Route::post('/logout', function () {
    Auth::logout(); 
    request()->session()->invalidate(); 
    request()->session()->regenerateToken(); 
    request()->session()->flush(); 
    return redirect('/')->with('mensaje', 'Sesión cerrada correctamente');
})->name('logout');

Route::get('/api/consultar-dni/{dni}', function ($dni) {
    $persona = DB::table('sunat')->where('dni', $dni)->first();

    if ($persona) {
        return response()->json([
            'success' => true,
            'nombre' => $persona->nombre_completo,
            'fecha' => date('d/m/Y', strtotime($persona->fecha_nacimiento))
        ]);
    }
    return response()->json(['success' => false], 404);
});

Route::post('/api/finalizar-registro', function (Request $request) {
    try {
        $existe = DB::table('users')->where('dni', $request->dni)->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Este DNI ya está registrado.']);
        }

        DB::table('users')->insert([
            'dni' => $request->dni,
            'celular' => $request->celular,
            'face_vector' => json_encode($request->face_vector),
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
});

Route::post('/api/rostro/login', function (Request $request) {
    $vectorActual = $request->vector_actual;
    $usuarios = DB::table('users')->get();

    foreach ($usuarios as $user) {
        $vectorGuardado = json_decode($user->face_vector);
        if (!$vectorGuardado) continue;

        $distancia = 0;
        for ($i = 0; $i < count($vectorActual); $i++) {
            $distancia += pow($vectorActual[$i] - $vectorGuardado[$i], 2);
        }
        $distancia = sqrt($distancia);

        if ($distancia < 0.7) {
            $datosPersonales = DB::table('sunat')->where('dni', $user->dni)->first();
            $nombreReal = $datosPersonales ? $datosPersonales->nombre_completo : 'Usuario';

            Auth::loginUsingId($user->id);
            session(['usuario_nombre' => $nombreReal]);

            return response()->json([
                'success' => true, 
                'usuario' => $nombreReal 
            ]);
        }
    }
    return response()->json(['success' => false, 'message' => 'No reconocido'], 401);
});