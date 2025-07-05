<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{

    public function index()
    {
        
        $appointment = Appointment::all();
    
        return response()->json($appointment, 200);
    }



    public function indexPerDay($date = null)
    {
        $date = $date ?? Carbon::today()->toDateString();
    
        $appointments = Appointment::where('scheduled_date', $date)
            ->with(['patient', 'doctor'])
            ->orderBy('start_time', 'asc')
            ->get();
    
        return response()->json($appointments);
    }
    public function indexAppointmentsToday()
{
    $today = Carbon::today()->toDateString();

    $appointments = Appointment::where('scheduled_date', $today)
        ->with('patient')
        ->orderBy('start_time', 'asc')
        ->get();

    return response()->json($appointments);
}
    
    


    // 1. عرض الأوقات المتاحة للطبيب في يوم محدد
    public function availableSlots($doctorId, $date)
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = strtolower($carbonDate->format('l'));

        $schedule = Schedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json(['message' => 'Doctor is not available on this day'], 404);
        }

        $start = Carbon::createFromFormat('H:i:s', $schedule->start_time);
        $end = Carbon::createFromFormat('H:i:s', $schedule->end_time);

        $slots = [];

        while ($start->lt($end)) {
            $slotStart = $start->copy();

            $exists = Appointment::where('doctor_id', $doctorId)
                ->where('scheduled_date', $date)
                ->where('start_time', $slotStart->format('H:i:s'))
                ->where('status', '!=', 'cancelled')
                ->exists();

            $slots[] = [
                'start_time' => $slotStart->format('H:i:s'),
                'available' => !$exists,
            ];

            $start->addMinutes(30);
        }

        return response()->json($slots);
    }

    // 2. إنشاء موعد جديد
    public function create(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'scheduled_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
        ]);

        $carbonDate = Carbon::parse($request->scheduled_date);
        $dayOfWeek = strtolower($carbonDate->format('l'));

        $schedule = Schedule::where('doctor_id', $request->doctor_id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json(['error' => 'Doctor is not available on this day.'], 400);
        }

        $startTime = Carbon::createFromFormat('H:i:s', $request->start_time);
        $scheduleStart = Carbon::createFromFormat('H:i:s', $schedule->start_time);
        $scheduleEnd = Carbon::createFromFormat('H:i:s', $schedule->end_time);

        if ($startTime->lt($scheduleStart) || $startTime->gte($scheduleEnd)) {
            return response()->json(['error' => 'Selected time is outside of doctor\'s working hours.'], 400);
        }

        $exists = Appointment::where('doctor_id', $request->doctor_id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where('start_time', $request->start_time)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'This time slot is already booked.'], 400);
        }

        $appointment = Appointment::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'scheduled_date' => $request->scheduled_date,
            'start_time' => $request->start_time,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Appointment booked successfully.', 'appointment' => $appointment], 201);
    }

    // 3. تعديل موعد وتحديث الحالة إلى pending
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'scheduled_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
        ]);

        $carbonDate = Carbon::parse($request->scheduled_date);
        $dayOfWeek = strtolower($carbonDate->format('l'));

        $schedule = Schedule::where('doctor_id', $request->doctor_id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json(['error' => 'Doctor is not available on this day.'], 400);
        }

        $startTime = Carbon::createFromFormat('H:i:s', $request->start_time);
        $scheduleStart = Carbon::createFromFormat('H:i:s', $schedule->start_time);
        $scheduleEnd = Carbon::createFromFormat('H:i:s', $schedule->end_time);

        if ($startTime->lt($scheduleStart) || $startTime->gte($scheduleEnd)) {
            return response()->json(['error' => 'Selected time is outside of doctor\'s working hours.'], 400);
        }

        $exists = Appointment::where('doctor_id', $request->doctor_id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where('start_time', $request->start_time)
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'This time slot is already booked.'], 400);
        }

        $oldTime = $appointment->start_time;

        $appointment->update([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'scheduled_date' => $request->scheduled_date,
            'start_time' => $request->start_time,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully. Old slot is now available.',
            'old_start_time' => $oldTime,
            'new_appointment' => $appointment
        ]);
    }

    // 4. إلغاء موعد
    public function cancel($id)
    {
        $appointment = Appointment::find($id);
       
        

        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        $appointment->status = 'cancelled';
        $appointment->save();

        return response()->json(['message' => 'Appointment cancelled successfully.']);
    }

    // 5. تحديث حالة الموعد (pending - confirmed - done - cancelled)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,done,cancelled',
        ]);
    
        $appointment = Appointment::find($id);
        $message = 'Appointment status updated.';
    
    if (in_array($appointment->status, ['done', 'cancelled'])) {
        return response()->json([
                'error' => 'Cannot update status. Appointment is already ' . $appointment->status . '.'
            ], 400);
        }
        if (!$appointment) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }
    
        $appointment->status = $request->status;
        $appointment->save();
    
        // ✅ إذا أصبحت الحالة "done"، أنشئ سجل دفع تلقائيًا
        if ($request->status === 'done') {
            // تأكد أولًا أنه لم يتم إنشاء الدفع مسبقًا
            $existingPayment = Payment::where('appointment_id', $appointment->id)->first();
    
            if (!$existingPayment) {
                Payment::create([
                    'patient_id' => $appointment->patient_id,
                    'appointment_id' => $appointment->id,
                    'amount' => 50.00,
                    'payment_type' => 'cash',
                    'payment_date' => now(),
                    'description' => 'Automatic payment after appointment completion',
                ]);
    
                $message .= ' Payment has been created successfully.';
            } else {
                $message .= ' Payment already exists.';
            }
        }
    
        return response()->json([
            'message' => 'Appointment status updated.',
            'appointment' => $appointment,
          
        ]);
    }
    
    public function patientAppointments($patientId)
{
    $appointments = Appointment::where('patient_id', $patientId)
        ->with(['doctor']) // لو أردت عرض معلومات عن الطبيب
        ->orderBy('scheduled_date', 'desc')
        ->get();

    return response()->json($appointments);
}


public function doctorAppointments($doctorId)
{
    $appointments = Appointment::where('doctor_id', $doctorId)
        ->with(['patient']) // لو حابب تعرض معلومات المريض
        ->orderBy('scheduled_date', 'asc')
        ->orderBy('start_time', 'asc')
        ->get();

    return response()->json($appointments);
}


public function doctorAppointmentsByDate(Request $request, $doctorId)
{
    $request->validate([
        'date' => 'required|date',
    ]);

    $appointments = Appointment::where('doctor_id', $doctorId)
        ->where('scheduled_date', $request->date)
        ->with(['patient'])
        ->orderBy('start_time', 'asc')
        ->get();

    return response()->json($appointments);
}
public function doctorAppointmentsToday($doctorId)
{
    $today = Carbon::today()->toDateString();

    $appointments = Appointment::where('doctor_id', $doctorId)
        ->where('scheduled_date', $today)
        ->with('patient')
        ->orderBy('start_time', 'asc')
        ->get();

    return response()->json($appointments);
}

public function show($id)
{
    $appointment = Appointment::with(['doctor', 'patient'])
        ->find($id);

    if (!$appointment) {
        return response()->json(['error' => 'Appointment not found'], 404);
    }

    return response()->json($appointment);
}


}
