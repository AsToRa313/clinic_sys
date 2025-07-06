<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clinic;
use App\Models\Rating;
use App\Models\Doctor;

use App\Models\Patient;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $patientId = auth()->user()->patient->id;

    //  Check if patient already rated this doctor
    $existingRating = Rating::where('doctor_id', $request->doctor_id)
                            ->where('patient_id', $patientId)
                            ->first();

    if ($existingRating) {
        return response()->json(['error' => 'You have already rated this doctor.'], 409);
    }

        $rating = Rating::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $patientId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($rating, 201);
    }

    public function index($doctorId)
    {
        $ratings = Rating::where('doctor_id', $doctorId)
            ->with('patient.user:id,first_name,last_name')
            ->latest()
            ->get();

        return response()->json($ratings);
    }



    public function showAvg($id)
    {
        $doctor = Doctor::with('user:id,first_name,email')->findOrFail($id);
    
        return response()->json([
            'id' => $doctor->id,
            'name' => $doctor->user->first_name,
            'email' => $doctor->user->email,
            'specialty' => $doctor->specialty,
            'clinic_id' => $doctor->clinic_id,
            'average_rating' => $doctor->ratings()->avg('rating') ?? 0,
            'ratings_count' => $doctor->ratings()->count(),
        ]);
    }
    
}

