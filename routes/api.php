<?php

use App\Http\Controllers\ClinicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Doctor\ScheduleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\DoctorNoteController;

use App\Http\Controllers\RatingController;

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
Route::post('/login', [UserController::class, 'login']); // تسجيل الدخول
Route::post('/register', [UserController::class, 'register']);//انشاء مستخدم
Route::get('/search/patients', [UserController::class, 'searchPatientsByName']);//بحث عن المريض حسب الاسم 
Route::get('/search/doctors', [UserController::class, 'searchDoctorsByName']);//ظظبحث عن طبيب حسب الاسم 


Route::middleware('auth:sanctum')->group(function(){
    Route::get('/available/{doctorId}/{date}', [AppointmentController::class, 'availableSlots']);//المواعيد الموجودة
    Route::post('/profile', [UserController::class, 'update']);//تعديل البروفايل
    Route::post('/logout', [UserController::class, 'logout']);//تسجيل الخروج
    Route::get('clinics', [ClinicController::class, 'index']);//عرض العيادات list of 
    Route::get('clinics/doctors/{id}', [ClinicController::class, 'show']);// عرض عيادة بدكاترتها
    Route::get('/doctors/{id}/schedules', [ScheduleController::class, 'show']);//عرض جدول دوام الدكتور
    Route::get('/ratings/{doctorId}', [RatingController::class, 'index']);//تقييمات الدكتور
    Route::get('/ratings/avg/{doctorId}', [RatingController::class, 'showAvg']);//ظظمتوسط تقييمات الدكتوريض

});





//doctor api
Route::middleware(['auth:sanctum', 'can:is-doctor'])->prefix('doctor')->group(function () {
    Route::get('schedules', [ScheduleController::class, 'index']);//عرض الجداول الخاصة بالطبيب نفسه
    Route::post('schedules', [ScheduleController::class, 'store']);//ادخال جدوا عمل خاص بالطبيب
    Route::delete('schedules/{id}', [ScheduleController::class, 'destroy']);//حذف جدول دوام خاص الطبيب
    Route::get('/appointments/{doctorId}', [AppointmentController::class, 'doctorAppointments']);//رؤية مواعيد الدكتور كافة
    Route::post('/appointments/{doctorId}/by-date', [AppointmentController::class, 'doctorAppointmentsByDate']);//عرض جدول المواعيد حسب يوم محدد
    Route::post('/appointments/today/{doctorId}', [AppointmentController::class, 'doctorAppointmentsToday']);//ظظعرض جدوا مواعيد اليوم
    Route::get('/appointments/{appointment}/notes', [DoctorNoteController::class, 'index']);//رؤية الملاحظات التي وضعها الطبيب لموعد محدد
    Route::post('/appointments/{appointment}/notes/store', [DoctorNoteController::class, 'store']);//تسجيل ملاحظة للمريض
    Route::put('/notes/{doctorNote}', [DoctorNoteController::class, 'update']);//التعديل على ملاحظة
    Route::delete('/notes/{doctorNote}', [DoctorNoteController::class, 'destroy']);//حذف ملاحظة
    Route::get('/search/patients', [UserController::class, 'searchPatientsByName']);//بحث عن المريض حسب الاسم 
});
// admin api
Route::middleware(['auth:sanctum', 'can:is-admin'])-> prefix('admin')->group(function () {
    //wallets
    Route::post('wallet/recharge/{patientId}', [WalletController::class, 'recharge']);//شحن محفظة لمريض
    Route::post('wallet/empty/{patientId}', [WalletController::class, 'empty']);//افراغ محفظة مريض
    Route::get('wallet/{patientId}', [WalletController::class, 'show']);//عرض محفظة مريض
    Route::get('wallet/system/show', [WalletController::class, 'showSystemWallet']);//عرض محفظة النظام
    Route::get('/wallet/system/transactions', [WalletController::class, 'systemWalletTransactions']);//عرض حركة المحفظة
//clinics
    Route::post('/create-clinic', [ClinicController::class, 'store']);//انشاء عيادة
    Route::post('clinics/update/{id}', [ClinicController::class, 'update']);//update
    Route::delete('clinics/delete/{id}', [ClinicController::class, 'destroy']);//حذف عيادة
    //users
    Route::post('/addUser', [UserController::class, 'adminAddUser']);//اضافة يوزر
    Route::delete('/deleteUser/{id}', [UserController::class, 'destroy']);//حذف يوزر
     Route::get('/doctors/{doctorId}/notes', [DoctorNoteController::class, 'doctorNotes']);//رؤية كل المحلاظات التي وضعها الطبيب
     Route::get('/patients/{patientId}/notes', [DoctorNoteController::class, 'patientNotes']);//رؤية كل الملاحظات التي وضعت للمريض
     Route::get('/search/patients', [UserController::class, 'searchPatientsByName']);//بحث عن المريض حسب الاسم 
     Route::get('/search/doctors', [UserController::class, 'searchDoctorsByName']);//ظظبحث عن طبيب حسب الاسم 

});


///receptionist api
Route::middleware(['auth:sanctum', 'is-receptionist'])-> prefix('receptionist')->group(function(){
    Route::post('/book', [AppointmentController::class, 'create']);//انشاء موعد
    Route::put('/cancel/{id}', [AppointmentController::class, 'cancel']);//الغاء موعد
    Route::put('/status/{id}', [AppointmentController::class, 'updateStatus']);//تعديل حالة الموعد
    Route::put('/update/{id}', [AppointmentController::class, 'update']);//تعديل الموعد
    Route::get('/appointment/{id}', [AppointmentController::class, 'show']);//اظهار موعد
    Route::POST('appointment/get/all/{date}', [AppointmentController::class, 'indexPerDay']);//اظهار المواعيد الخاصة بيوم محدد
    Route::get('appointment/get/all', [AppointmentController::class, 'index']);//احضار كل المواعيد
    Route::get('appointment/get/all/today', [AppointmentController::class, 'indexAppointmentsToday']);//ظظاحضار المواعيد الخاصة باليوم
    Route::get('appointment/available/{doctorId}/{date}', [AppointmentController::class, 'availableSlots']);//احضار جدول دوام دكتور معين
    Route::get('payments/get/all', [PaymentController::class, 'index']);//احضار المدفوعات
    Route::get('payments/{id}', [PaymentController::class, 'show']);//تفاصيل فاتورة
    Route::get('/appointments/get/patient/{id}', [AppointmentController::class, 'patientAppointments']);//احضار المواعيد الخاصة بمريض
    Route::delete('payments/destroy/{id}', [PaymentController::class, 'destroy']);//حذف فاتورة
    Route::get('/search/patients', [UserController::class, 'searchPatientsByName']);//بحث عن المريض حسب الاسم 
    Route::get('/search/doctors', [UserController::class, 'searchDoctorsByName']);//ظظبحث عن طبيب حسب الاسم 


});
Route::middleware(['auth:sanctum', 'is-patient'])-> prefix('patient')->group(function(){
    Route::get('wallet', [WalletController::class, 'showWallet']);//الطلاع على المحفظة
    Route::post('/book', [AppointmentController::class, 'createPatient']);//انشاء موعد
    Route::put('/cancel/{id}', [AppointmentController::class, 'cancel']);//الغاء موعد
    Route::put('/status/{id}', [AppointmentController::class, 'updateStatusByPatient']);//تعديل حالة موعد خاص بالمريض
    Route::put('/update/{id}', [AppointmentController::class, 'updateFromPatient']);//تعديل على الموعد من قبل المريض
    Route::get('/appointment/{id}', [AppointmentController::class, 'showPatient']);//عرض الموعد
    Route::get('/appointments/get', [AppointmentController::class, 'patientAppointmentsBy']);//رؤية المواعيد الخاصة بالمريض
    Route::get('/wallet/transactions/{id}', [WalletController::class, 'transactions']);
    Route::get('payment/{id}', [PaymentController::class, 'showPatient']);//اظهار تفاصيل البايمنت
    Route::get('payments/get/all', [PaymentController::class, 'indexOwnPayments']);//احضار المدفوعات الخاصة بالمريض
    Route::post('payments/pay/{id}', [PaymentController::class, 'payFromWallet']);//دفع الفاتورة
    Route::get('/appointments/{appointment}/notes', [DoctorNoteController::class, 'index']);//رؤية كل الملاحظات التي وضعها الدكتور الخاصة بموعد
    Route::post('/ratings', [RatingController::class, 'store']);//تقييم دكتور
    Route::get('/notes/{doctorNote}', [DoctorNoteController::class, 'show']);//رؤية تفاصيل ملاحظة
    Route::get('/search/doctors', [UserController::class, 'searchDoctorsByName']);//ظظبحث عن طبيب حسب الاسم 

    


});


///////// مو جاهز لسا بدو تنضيف اكتر وترتيب لا حدا يربط منو الا اذا فاهم شو الشغل
//اربطوا بس لي محطوط بقلب الgates<?php

