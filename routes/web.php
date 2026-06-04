<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthFacialController; 
use App\Http\Controllers\SenaController;
use App\Models\Doctor;
use App\Http\Controllers\CitaController;

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