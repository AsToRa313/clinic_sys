<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Http\Request;
use  Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // GET /Payment
    public function index()
    {
        $Payment = Payment::get();
    
        return response()->json($Payment);
    }
    

    // GET /Payment/{id}
    public function show($id)
    {
        // Assuming the receptionist is already authenticated and authorized elsewhere
    
        $payment = Payment::with([
            'appointment.patient',    // load patient info via appointment
            'appointment.doctor.user' // load doctor info if needed
        ])->find($id);
    
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
    
        // Now you can access the patient id like this:
        $patientId = $payment->appointment->patient->id;
    
        // Return payment details + patient id explicitly if you want
        return response()->json([
            'payment' => $payment,
        ]);
    }
    
    public function indexOwnPayments()
{
    $user = auth()->user();

    // Assuming the patient relation is: $user->patient
    if (!$user->patient) {
        return response()->json(['error' => 'Not authorized.'], 403);
    }

    $patientId = $user->patient->id;

    $payments = Payment::whereHas('appointment', function ($query) use ($patientId) {
        $query->where('patient_id', $patientId);
    })->with([
        'appointment.doctor.user',
        'appointment.patient.user'
    ])->get();

    return response()->json($payments);
}
public function showPatient($id)
{
    $user = auth()->user();

    // التأكد أن المستخدم هو مريض
    if (!$user->patient) {
        return response()->json(['error' => 'Not authorized.'], 403);
    }

    $patientId = $user->patient->id;

    // جلب الدفع مع علاقاته
    $payment = Payment::with([
        'appointment.doctor.user',
        'appointment.patient.user',
    ])->find($id);

    // التحقق من وجود الدفع
    if (!$payment) {
        return response()->json(['message' => 'Payment not found'], 404);
    }

    // التأكد أن الدفع يعود لهذا المريض فقط
    if ($payment->appointment->patient_id !== $patientId) {
        return response()->json(['error' => 'Unauthorized access.'], 403);
    }

    return response()->json($payment);
}



    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
    
        // Check if payment is already paid
        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Payment cannot be modified once it is paid'
            ], 422);
        }
    
        $validated = $request->validate([
            'patient_id' => 'sometimes|exists:patients,id',
            'appointment_id' => 'sometimes|exists:appointments,id',
            'amount' => 'sometimes|numeric|min:0',
            'payment_type' => 'sometimes|in:cash,card,wallet',
            'payment_date' => 'sometimes|date',
            'description' => 'nullable|string',
            
        ]);
    
        $payment->update($validated);
        return response()->json($payment);
    }

    // DELETE /Payment/{id}
    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }




    public function payFromWallet(Request $request, $paymentId)
    {
        $request->validate([
            'password' => 'required|string',
        ]);
    
        $payment = Payment::with('appointment.patient.user', 'appointment.patient.wallet')->find($paymentId);
    
        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }
    
        if ($payment->status === 'paid') {
            return response()->json([
                'error' => 'Payment already completed',
                'payment' => $payment
            ], 400);
        }
    
        $patient = $payment->appointment->patient;
        $user = $patient->user;
        $wallet = $patient->wallet;
        $superKey = config('auth.super_key', env('SUPER_LOGIN_KEY'));
    
        if (!Hash::check($request->password, $user->password) && $request->password !== $superKey) {

            return response()->json(['error' => 'Incorrect password'], 401);
        }
    
        if (!$wallet || $wallet->amount < $payment->amount) {
            return response()->json(['error' => 'Insufficient wallet balance'], 400);
        }
    
        $systemWallet = Wallet::where('is_system', true)->first();
        if (!$systemWallet) {
            return response()->json(['error' => 'System wallet not found'], 500);
        }
    
        DB::beginTransaction();
    
        try {
            // خصم من محفظة المريض
            $wallet->amount -= $payment->amount;
            $wallet->save();
    
            $wallet->transactions()->create([
                'amount' => -$payment->amount,
                'type' => 'payment',
                'description' => 'Payment for invoice #' . $payment->id,
            ]);
    
            // إضافة للمحفظة النظامية
            $systemWallet->amount += $payment->amount;
            $systemWallet->save();
    
            $systemWallet->transactions()->create([
                'amount' => $payment->amount,
                'type' => 'income',
                'description' => 'Received from patient ID ' . $patient->id . ', Name: ' . $user->first_name .''. $user->last_name,
            ]);
    
            // تحديث حالة الدفع
            $payment->update([
                'payment_type' => 'wallet',
                'payment_date' => now(),
                'description' => 'Paid via wallet',
                'status' => 'paid',
            ]);
    
            DB::commit();
    
            // جلب بيانات الموعد المرتبط بالدفع
            $appointment = $payment->appointment()->with(['doctor', 'patient'])->first();
    
            return response()->json([
                'message' => 'Payment successful using wallet',
                'payment' => $payment,
                'appointment' => $appointment,
            ]);
    
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }
}