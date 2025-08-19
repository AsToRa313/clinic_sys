<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Wallet;

class DashboardController extends Controller
{
    /**
     * جلب الإحصائيات الرئيسية لعرضها في لوحة تحكم الأدمن.
     */
    public function getStats()
    {
        try {
            $today = now()->toDateString();

            $stats = [
                'today_appointments' => Appointment::whereDate('scheduled_date', $today)->count(),
                'total_patients' => Patient::count(),
                'total_doctors' => Doctor::count(),
                'clinic_earning' => Wallet::where("is_system",true)->first()->amount,
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch dashboard stats.'], 500);
        }
    }
}
