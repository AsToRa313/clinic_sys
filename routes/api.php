<?php

use App\Http\Controllers\ClinicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Doctor\ScheduleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;
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

Route::get('/patient/{patientId}', [AppointmentController::class, 'patientAppointments']);



//doctor api
Route::middleware(['auth:sanctum', 'can:is-doctor'])->prefix('doctor')->group(function () {
    Route::get('schedules', [ScheduleController::class, 'index']);
    Route::post('schedules', [ScheduleController::class, 'store']);
    Route::delete('schedules/{id}', [ScheduleController::class, 'destroy']);
    Route::get('/doctor/{doctorId}', [AppointmentController::class, 'doctorAppointments']);
    Route::post('/doctor/{doctorId}/by-date', [AppointmentController::class, 'doctorAppointmentsByDate']);
    Route::post('/doctor/today/{doctorId}', [AppointmentController::class, 'doctorAppointmentsToday']);
});
// admin api
Route::middleware(['auth:sanctum', 'can:is-admin'])->group(function () {
    
    Route::post('/create-clinic', [ClinicController::class, 'store']);
    Route::post('clinics/{id}', [ClinicController::class, 'update']);
    Route::delete('clinics/{id}', [ClinicController::class, 'destroy']);
    Route::post('/addUser', [UserController::class, 'adminAddUser']);
    Route::post('/deleteUser/{id}', [UserController::class, 'destroy']);
});

Route::prefix('appointments')->group(function () {
    Route::get('/available/{doctorId}/{date}', [AppointmentController::class, 'availableSlots']);
    Route::post('/book', [AppointmentController::class, 'create']);
    Route::put('/cancel/{id}', [AppointmentController::class, 'cancel']);
    Route::put('/status/{id}', [AppointmentController::class, 'updateStatus']);
    Route::put('/update/{id}', [AppointmentController::class, 'update']);

    Route::get('/appointment/{id}', [AppointmentController::class, 'show']);


});
///receptionist api
Route::middleware(['auth:sanctum', 'is-receptionist'])-> prefix('receptionist')->group(function(){
    Route::post('/book', [AppointmentController::class, 'create']);
    Route::put('/cancel/{id}', [AppointmentController::class, 'cancel']);
    Route::put('/status/{id}', [AppointmentController::class, 'updateStatus']);
    Route::put('/update/{id}', [AppointmentController::class, 'update']);
    Route::get('/appointment/{id}', [AppointmentController::class, 'show']);
    Route::POST('/all/{date}', [AppointmentController::class, 'indexPerDay']);
    Route::get('/all', [AppointmentController::class, 'index']);
    Route::get('/all/today', [AppointmentController::class, 'indexAppointmentsToday']);
    Route::get('/available/{doctorId}/{date}', [AppointmentController::class, 'availableSlots']);


});
Route::middleware(['auth:sanctum', 'is-patient'])-> prefix('patient ')->group(function(){
    Route::post('/book', [AppointmentController::class, 'create']);
    Route::put('/cancel/{id}', [AppointmentController::class, 'cancel']);
    Route::get('/available/{doctorId}/{date}', [AppointmentController::class, 'availableSlots']);
    Route::put('/status/{id}', [AppointmentController::class, 'updateStatus']);
    Route::put('/update/{id}', [AppointmentController::class, 'update']);
    Route::get('/appointment/{id}', [AppointmentController::class, 'show']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'patientAppointments']);



});

Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
    Route::post('/pay/{id}', [PaymentController::class, 'payFromWallet']);


});
// wallet basic

Route::prefix('wallet')->group(function () {
    Route::get('/{patientId}', [WalletController::class, 'show']);          // عرض الرصيد
    Route::post('/recharge/{patientId}', [WalletController::class, 'recharge']);  // تعبئة المحفظة
    Route::post('/empty/{patientId}', [WalletController::class, 'empty']);      // إفراغ المحفظة
});