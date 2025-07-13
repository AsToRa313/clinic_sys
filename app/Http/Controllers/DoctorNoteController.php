<?php

namespace App\Http\Controllers;

use App\Models\DoctorNote;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DoctorNoteController extends Controller
{
    /**
     * Display a listing of doctor notes for a specific appointment.
     */
    public function index(Appointment $appointment)
    {
        // Verify the authenticated user has access to these notes
        $user = Auth::user();
        
        if ($user->role === 'patient' && $appointment->patient_id !== $user->patient->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($user->role === 'doctor' && $appointment->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notes = $appointment->doctorNote() ->get();

        return response()->json($notes);
    }

    /**
     * Store a newly created doctor note.
     */
    public function store(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        
        // Only doctors can create notes
        if ($user->role !== 'doctor') {
            return response()->json(['message' => 'Only doctors can create notes'], 403);
        }
        
        // Verify the doctor is assigned to this appointment
        if ($appointment->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'You are not assigned to this appointment'], 403);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
            'prescription' => 'nullable|string|max:1000',
            'is_private' => 'boolean'
        ]);

        $note = DoctorNote::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $user->doctor->id,
            'patient_id' => $appointment->patient_id,
            ...$validated
        ]);

        return response()->json($note, Response::HTTP_CREATED);
    }

    /**
     * Display the specified doctor note.
     */
    public function show(DoctorNote $doctorNote)
    {
        $user = Auth::user();
        
        // Check access rights
        if ($user->role === 'patient' && $doctorNote->patient_id !== $user->patient->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($user->role === 'doctor' && $doctorNote->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doctorNote->load(['doctor.user', 'patient.user', 'appointment']);

        return response()->json($doctorNote);
    }

    /**
     * Update the specified doctor note.
     */
    public function update(Request $request, DoctorNote $doctorNote)
    {
        $user = Auth::user();
        
        // Only the creating doctor or admin can update
        if ($user->role !== 'admin' && 
            ($user->role !== 'doctor' || $doctorNote->doctor_id !== $user->doctor->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'notes' => 'sometimes|string|max:2000',
            'prescription' => 'nullable|string|max:1000',
            'is_private' => 'sometimes|boolean'
        ]);

        $doctorNote->update($validated);

        return response()->json($doctorNote);
    }

    /**
     * Remove the specified doctor note.
     */
    public function destroy(DoctorNote $doctorNote)
    {
        $user = Auth::user();
        
        // Only the creating doctor or admin can delete
        if ($user->role !== 'admin' &&
            ($user->role !== 'doctor' || $doctorNote->doctor_id !== $user->doctor->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doctorNote->delete();

        return response()->json('deleted ');
    }

    /**
     * Get notes for a specific patient
     */
    public function patientNotes($patientId)
    {
        $user = Auth::user();
        
        // Only doctors and the patient themselves can view patient notes
        if ($user->role === 'patient' && $user->patient->id != $patientId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($user->role === 'receptionist') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notes = DoctorNote::where('patient_id', $patientId)
            ->with(['doctor.user', 'appointment'])
            ->latest()
            ->get();

        return response()->json($notes);
    }

    /**
     * Get notes created by a specific doctor
     */
    public function doctorNotes($doctorId)
    {
        $user = Auth::user();
        
        // Only admins and the doctor themselves can view their notes
        if ($user->role !== 'admin' &&
            ($user->role !== 'doctor' || $user->doctor->id != $doctorId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notes = DoctorNote::where('doctor_id', $doctorId)
            ->with(['patient.user', 'appointment'])
            ->latest()
            ->get();

        return response()->json($notes);
    }
}