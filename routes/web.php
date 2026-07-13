<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthFacialController; 
use App\Http\Controllers\SenaController;
use App\Http\Controllers\SenaConversacionController;
use App\Models\Doctor;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DoctorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Vistas Principales ---

Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::get('/registro', function () {
    return view('auth.registro');
})->name('registro');

Route::get('/home', function () {
    return view('paginas.home.inicio');
})->name('home');

Route::get('/reservar-cita', [CitaController::class, 'reserva'])->name('cita.reserva');

Route::get('/cita-confirmada', [CitaController::class, 'confirmada'])->name('cita.confirmada');

Route::get('/seleccion-manual', [CitaController::class, 'seleccionManual'])->name('especialidad.manual');


Route::get('/reconocimiento-senas', function () {
    return view('paginas.on-señas.on-señas');
})->name('señas'); 

Route::post('/api/reconocer-sena', [SenaController::class, 'reconocerSena']);
Route::post('/api/reconocer-sena-conversacion', [SenaConversacionController::class, 'reconocer']);
Route::post('/api/senas-conversacion/muestras', [SenaConversacionController::class, 'guardarMuestra']);
Route::get('/api/senas-conversacion/frases', [SenaConversacionController::class, 'listarFrases']);
Route::delete('/api/senas-conversacion/frases/{codigo}', [SenaConversacionController::class, 'eliminarFrase']);

Route::post('/logout', function () {
    Auth::logout(); 
    request()->session()->invalidate(); 
    request()->session()->regenerateToken(); 
    request()->session()->flush(); 
    return redirect('/')->with('mensaje', 'Sesión cerrada correctamente');
})->name('logout');

Route::get('/api/consultar-dni/{dni}', [AuthFacialController::class, 'consultarDni']);
Route::post('/api/finalizar-registro', [AuthFacialController::class, 'finalizarRegistro']);
Route::post('/api/rostro/login', [AuthFacialController::class, 'loginRostro']);


//DOCTOR LOGICA
Route::prefix('doctor')->group(function () {
    Route::get('/panel', [DoctorController::class, 'panel'])->name('doctor.panel');
    Route::get('/api/citas-mes', [DoctorController::class, 'getCitasMes']);
    Route::post('/api/actualizar-estado-cita', [DoctorController::class, 'actualizarEstadoCita']);
    Route::get('/pacientes/{id}', [DoctorController::class, 'detallePaciente'])->name('doctor.detalle-paciente');
    Route::post('/pacientes/guardar-consulta/{citaId}', [DoctorController::class, 'guardarConsulta'])->name('doctor.guardar-consulta');
    Route::post('/pacientes/guardar-receta/{citaId}', [DoctorController::class, 'guardarReceta'])->name('doctor.guardar-receta');
    Route::post('/api/actualizar-motivo-cita', [DoctorController::class, 'actualizarMotivoCita']);
    Route::post('/api/actualizar-diagnostico-cita', [DoctorController::class, 'actualizarDiagnosticoCita']);
    Route::post('/api/guardar-anotacion-modal', [DoctorController::class, 'guardarAnotacionModal']);
    Route::post('/api/guardar-receta-medica', [DoctorController::class, 'guardarRecetaMedica']);
    Route::delete('/api/eliminar-receta-medica/{id}', [DoctorController::class, 'eliminarRecetaMedica']);
});
Route::get('/pantalla-paciente', function () {
    return view('paginas.doctor.traductor.pantalla-paciente');
})->name('doctor.pantalla-paciente');

Route::get('/traductor-senas', function () {
    return view('paginas.doctor.traductor.traductor-senas');
})->name('doctor.traductor-senas');

Route::post('/doctor/api/actualizar-bmi-paciente', [DoctorController::class, 'actualizarBmiPaciente'])->name('doctor.actualizarBmi');
