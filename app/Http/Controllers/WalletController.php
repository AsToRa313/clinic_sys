<?php

namespace App\Http\Controllers;

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


}
