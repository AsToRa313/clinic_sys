<?php


namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    // ✅ List all schedules for the authenticated doctor




    public function show($doctor_id)
    {
        $schedules = Schedule::where('doctor_id', $doctor_id)
            ->orderByRaw("FIELD(day_of_week, 'sunday','monday','tuesday','wednesday','thursday','friday','saturday')")
            ->get();
    
        if ($schedules->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No schedules found for this doctor.',
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'data' => $schedules,
        ]);
    }
    

    public function index()
    {
        $doctor = Auth::user()->doctor;
        $schedules = Schedule::where('doctor_id', $doctor->id)->get();

        return response()->json([
            'status' => true,
            'data' => $schedules,
        ]);
    }

    // ✅ Store or update schedule for a specific day
    public function store(Request $request)
    {
        $doctor = Auth::user()->doctor;

        $validated = $request->validate([
            'day_of_week' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule = Schedule::updateOrCreate(
            ['doctor_id' => $doctor->id, 'day_of_week' => $validated['day_of_week']],
            ['start_time' => $validated['start_time'], 'end_time' => $validated['end_time']]
        );

        return response()->json([
            'status' => true,
            'message' => 'Schedule saved successfully.',
            'data' => $schedule,
        ]);
    }

    // ✅ Delete schedule
    public function destroy($id)
    {
        $doctor = Auth::user()->doctor;
        $schedule = Schedule::where('id', $id)->where('doctor_id', $doctor->id)->first();

        if (!$schedule) {
            return response()->json([
                'status' => false,
                'message' => 'Schedule not found.',
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'status' => true,
            'message' => 'Schedule deleted successfully.',
        ]);
    }
}
