<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clinic;
use Illuminate\Support\Facades\DB;

class ClinicController extends Controller
{
   
    public function index()
    {
        // Only clinics, no doctors loaded here
        $clinics = Clinic::all();
    
        return response()->json($clinics, 200);
    }

    // Create a new clinic
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:124',
            'phone' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string|min:2|max:124',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $name = time() . '-' . $file->getClientOriginalName();
                $path = $file->storeAs('images', $name, 'public');
                $imagePath = 'storage/' . $path;
            }

            $clinic = Clinic::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'image' => $imagePath,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'clinic' => $clinic,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Show a single clinic by id
    public function show($id)
    {
        // Clinic with doctors loaded
        $clinic = Clinic::with('doctors.user')->find($id);
    
        if (!$clinic) {
            return response()->json(['error' => 'Clinic not found'], 404);
        }
    
        return response()->json($clinic, 200);
    }

    // Update a clinic
    public function update(Request $request, $id)
    {
        $clinic = Clinic::find($id);
        if (!$clinic) {
            return response()->json(['error' => 'Clinic not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|min:2|max:124',
            'phone' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string|min:2|max:124',
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $name = time() . '-' . $file->getClientOriginalName();
                $path = $file->storeAs('images', $name, 'public');
                $clinic->image = 'storage/' . $path;
            }

            if ($request->has('name')) {
                $clinic->name = $request->name;
            }
            if ($request->has('phone')) {
                $clinic->phone = $request->phone;
            }
            if ($request->has('description')) {
                $clinic->description = $request->description;
            }

            $clinic->save();

            DB::commit();

            return response()->json($clinic, 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a clinic
    public function destroy($id)
    {
        $clinic = Clinic::find($id);
        if (!$clinic) {
            return response()->json(['error' => 'Clinic not found'], 404);
        }

        try {
            $clinic->delete();
            return response()->json(['message' => 'Clinic deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
