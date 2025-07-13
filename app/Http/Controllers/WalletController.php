<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use App\Models\Patient;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    // عرض حالة المحفظة (الرصيد الحالي)
    public function show($patientId)
    {
        $wallet = Wallet::where('patient_id', $patientId)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        return response()->json(['amount' => $wallet->amount]);
    }
    public function showWallet()
    {
        $user = auth()->user();
    
        // Ensure the user is a patient
        if (!$user->patient) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $wallet = Wallet::where('patient_id', $user->patient->id)->first();
    
        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }
    
        return response()->json(['amount' => $wallet->amount]);
    }
    
    // تعبئة المحفظة (إضافة رصيد)
    public function recharge(Request $request, $patientId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $wallet = Wallet::firstOrCreate(
            ['patient_id' => $patientId],
            ['amount' => 0]
        );

        $wallet->amount += $request->amount;
        $wallet->save();
        $wallet->transactions()->create([
                'amount' => $request->amount,
                'type' => 'income',
                'description' => 'income from system #'
            ]);

        return response()->json(['message' => 'Wallet recharged successfully', 'wallet' => $wallet]);
    }

    // إفراغ المحفظة (تصفير الرصيد)
    public function empty($patientId)
    {
        $wallet = Wallet::where('patient_id', $patientId)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        $wallet->amount = 0;
        $wallet->save();
        $wallet->transactions()->create([
                'amount' => '0',
                'type' => 'adjustment',
                'description' => 'adjustment from system #'
            ]);

        return response()->json(['message' => 'Wallet emptied successfully', 'wallet' => $wallet]);
    }

    // عرض رصيد محفظة النظام
public function showSystemWallet()
{
    $wallet = Wallet::where('is_system', true)->first();

    if (!$wallet) {
        return response()->json(['message' => 'System wallet not found'], 404);
    }

    return response()->json(['system_wallet_balance' => $wallet->amount]);
}
public function systemWalletTransactions()
{
    // جلب محفظة النظام
    $systemWallet = Wallet::where('is_system', true)->first();

    if (!$systemWallet) {
        return response()->json(['error' => 'System wallet not found'], 404);
    }

    // جلب المعاملات المرتبطة
    $transactions = $systemWallet->transactions()->latest()->get()->map(function ($t) {
        return [
            'amount' => $t->amount,
            'type' => $t->type,
            'description' => $t->description,
            'date' => $t->created_at->format('Y-m-d H:i'),
        ];
    });

    return response()->json([
        'wallet_balance' => $systemWallet->amount,
        'transactions' => $transactions,
    ]);
}






public function transactions()
{
    // هنا نجيب المريض بناءً على المستخدم الحالي (مسجل الدخول)
    $user = Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $patient = $user->patient;

    if (!$patient) {
        return response()->json(['error' => 'Patient not found'], 404);
    }
    $user = Auth::user();
    $patientId = $user->patient->id;
    
    $wallet = $patient->wallet;

    if (!$wallet) {
        return response()->json(['error' => 'Wallet not found'], 404);
    }

    $transactions = $wallet->transactions()->latest()->get()->map(function ($t) {
        return [
            'amount' => $t->amount,
            'type' => $t->type,
            'description' => $t->description,
            'date' => $t->created_at->format('Y-m-d H:i'),
        ];
    });

    return response()->json([
        'wallet_balance' => $wallet->amount,
        'transactions' => $transactions,
    ]);
}



}
