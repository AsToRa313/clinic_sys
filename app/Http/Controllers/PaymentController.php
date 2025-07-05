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
        $Payment = Payment::with([
            'appointment.doctor.user',
            'appointment.patient.user'
        ])->get();
    
        return response()->json($Payment);
    }
    

    // GET /Payment/{id}
    public function show($id)
    {
        $payment = Payment::with(['patient', 'appointment'])->find($id);
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
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
           // Check if payment is already paid
    if ($payment->status === 'paid') {
        return response()->json([
            'error' => 'Payment already completed',
            'payment' => $payment
        ], 400);
    }
    
        $patient = $payment->appointment->patient;
        $user = $patient->user;
        $wallet = $patient->wallet;
    
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Incorrect password'], 401);
        }
    
        if (!$wallet || $wallet->amount < $payment->amount) {
            return response()->json(['error' => 'Insufficient wallet balance'], 400);
        }
    
        // محفظة النظام
       /* $systemWallet = Wallet::where('is_system', true)->first();
        if (!$systemWallet) {
            return response()->json(['error' => 'System wallet not found'], 500);
        }*/
    
        // بدء المعاملة لضمان تكامل العملية
        DB::beginTransaction();
    
        try {
            // خصم من محفظة المريض
            $wallet->amount -= $payment->amount;
            $wallet->save();
    
        /*    $wallet->transactions()->create([
                'amount' => -$payment->amount,
                'type' => 'payment',
                'description' => 'Payment for invoice #' . $payment->id,
            ]);*/
    
            // تحويل إلى محفظة النظام
           /* $systemWallet->amount += $payment->amount;
            $systemWallet->save();
    
            $systemWallet->transactions()->create([
                'amount' => $payment->amount,
                'type' => 'income',
                'description' => 'Received from patient ID ' . $patient->id,
            ]);*/
    
            // تحديث حالة الدفع
            $payment->update([
                'payment_type' => 'wallet', // Add quotes around 'wallet'
                'payment_date' => now(),
                'description' => 'Paid via wallet',
                'status' => 'paid',
            ]);;
    
            DB::commit();
    
            return response()->json(['message' => 'Payment successful using wallet', 'payment' => $payment]);
    
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }
}
