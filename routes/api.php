<?php

use App\Http\Controllers\ClinicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Doctor\ScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/profile', [UserController::class, 'update']);
    Route::post('/logout', [UserController::class, 'logout']);

});

Route::get('clinics', [ClinicController::class, 'index']);
Route::get('clinics/{id}', [ClinicController::class, 'show']);

Route::get('/doctors/{id}/schedules', [ScheduleController::class, 'show']);




Route::middleware(['auth:sanctum', 'can:is-doctor'])->prefix('doctor')->group(function () {
    Route::get('schedules', [ScheduleController::class, 'index']);
    Route::post('schedules', [ScheduleController::class, 'store']);
    Route::delete('schedules/{id}', [ScheduleController::class, 'destroy']);
});
Route::middleware(['auth:sanctum', 'can:is-admin'])->group(function () {
    
    Route::post('/create-clinic', [ClinicController::class, 'store']);
    Route::post('clinics/{id}', [ClinicController::class, 'update']);
    Route::delete('clinics/{id}', [ClinicController::class, 'destroy']);
});

