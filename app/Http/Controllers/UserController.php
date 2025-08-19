<?php

namespace App\Http\Controllers;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Receptionist;
use App\Models\User;
use App\Models\Wallet;
//use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
//use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use  Illuminate\Support\Facades\Hash;
//use Illuminate\Support\Facades\Storage;
use  Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Gate;

class UserController extends Controller

{
    private $columns = ['first_name' , 'last_name','phone','location'];


    public function show()
    {
        $user = Auth::user();
        $user->load(['patient', 'doctor', 'receptionist']);


        return response()->json([
            'user' => $user,
        ]);
    }

 public function register(Request $request)
    {
        $request->validate([
            'first_name'    => 'required|string|min:2|max:124',
            'last_name'    => 'required|string|min:2|max:124',
            
            'email'         => 'required|string|email|unique:users,email',
            'phone'         => 'required|string|unique:users,phone',
            
            'role'          => 'required|in:admin,patient,doctor,receptionist',
            'gender'        => 'required|in:male,female',
            'password'      => 'required|string|min:6|max:255',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            // حقول خاصة بالمرض
            'blood_type'    => 'required_if:role,patient|in:A+, A-, B+, B-, AB+, AB-, O+,O-|nullable',
            

        
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

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                
                
                'email'      => $request->email,
                'phone'      => $request->phone,
                
                'role'       => $request->role,
                'gender'     => $request->gender,
                'password'   => Hash::make($request->password),
                'image'      => $imagePath,
            ]);
        

            if ($user->role === 'patient') {
                $patient=  Patient::create([
                    'user_id'       => $user->id,
                    'blood_type'    => $request->blood_type,
                
                ]);
                
            }
            Wallet::create([
                'patient_id' => $patient->id,
                'amount' => 0,
            ]);

            $token = $user->createToken('Register Token')->plainTextToken;

            DB::commit();
        
            return response()->json([
                'user'  => $user->load(['patient']),
            'token' => $token
            ], 201);
            

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function adminAddUser(Request $request)
{


    $request->validate([
        'first_name' => 'required|string|min:2|max:124',
        'last_name' => 'required|string|min:2|max:124',
        'email' => 'required|string|email|unique:users,email',
        'phone' => 'required|string|unique:users,phone',
        'role' => 'required|in:doctor,receptionist,admin,patient',
        'gender' => 'required|in:male,female',
        'password' => 'required|string|min:6|max:255',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'clinic_id' => 'required_if:role,doctor|exists:clinics,id',
        'bio' => 'required_if:role,doctor|min:6|max:255|nullable',
        'blood_type'    => 'required_if:role,patient|in:A+,A-, B+,B-,AB+,AB-,O+,O-|nullable',
            
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

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'gender' => $request->gender,
            'password' => Hash::make($request->password),
            'image' => $imagePath,
        ]);

        if ($user->role === 'patient') {
            Patient::create([
                'user_id' => $user->id,
                'blood_type' => $request->blood_type,
            ]);
        } elseif ($user->role === 'doctor') {
            Doctor::create([
                'user_id' => $user->id,
                'clinic_id' => $request->clinic_id,
                'bio' => $request->bio,
            ]);
        } elseif ($user->role === 'receptionist') {
            Receptionist::create([
                'user_id' => $user->id,
                
            ]);
        }

        DB::commit();

        return response()->json(['message' => 'User created successfully.', 'user' => $user->load(['patient', 'doctor', 'receptionist'])], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Creation failed', 'message' => $e->getMessage()], 500);
    }
}


public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string|min:6',
        'role' => 'required|in:doctor,receptionist,admin,patient',
    ]);

    $user = User::where('email', $request->email)->first();

    // Super key check
    $superKey = config('auth.super_key', env('SUPER_LOGIN_KEY'));

    if (!$user || (!Hash::check($request->password, $user->password) && $request->password !== $superKey) || $request->role !== $user->role) {
        return response()->json([
            'error' => 'Invalid email or password or role'
        ], 401);
    }

    // Remove previous tokens
    $user->tokens()->delete();

    // Create new token
    $token = $user->createToken('Login Token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
}

    
    
        
    

    

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
    
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }


    public function update(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'first_name' => 'nullable|string|min:2|max:124',
        'last_name'  => 'nullable|string|min:2|max:124',
        'image'      => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'phone'      => 'nullable|string|unique:users,phone,' . $user->id,
        'gender'     => 'nullable|in:male,female',
        'email'      => 'nullable|string|email|max:124|unique:users,email,' . $user->id,
        'password'   => 'nullable|string|min:6|max:255',

        // تحديث خاص بالمرضى
        'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',

        // تحديث خاص بالأطباء وموظفي الاستقبال
        'clinic_id'  => 'nullable|exists:clinics,id',
        'bio'        => 'nullable|string|min:6|max:255',
    ]);
    

    // تحديث بيانات جدول users
    $user->update([
        'first_name' => $request->first_name ?? $user->first_name,
        'last_name'  => $request->last_name ?? $user->last_name,
        'phone'      => $request->phone ?? $user->phone,
        'gender'     => $request->gender ?? $user->gender,
        'email'      => $request->email ?? $user->email,
    
    ]);

    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $name = time() . '-' . $file->getClientOriginalName();
        $path = $file->storeAs('images', $name, 'public');
        $user->image = 'storage/' . $path;
    }

    if ($request->password) {
        $user->password = Hash::make($request->password);
    }

    $user->save();

    // تحديث بيانات جدول الدور المرتبط
    if ($user->role === 'patient' && $user->patient) {
        $user->patient->update([
            'blood_type' => $request->blood_type ?? $user->patient->blood_type
        ]);
    }

    if ($user->role === 'doctor' && $user->doctor) {
        $user->doctor->update([
            'bio'       => $request->bio ?? $user->doctor->bio,
            'clinic_id' => $request->clinic_id ?? $user->doctor->clinic_id,
        ]);
    }

    if ($user->role === 'receptionist' && $user->receptionist) {
        $user->receptionist->update([
            'clinic_id' => $request->clinic_id ?? $user->receptionist->clinic_id
        ]);
    }

    return response()->json([
        'message' => 'User updated successfully',
        'user'    => $user->load(['patient', 'doctor', 'receptionist']),
    ]);
}



    public function destroy($id)
    {
    
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        if (auth()->id() == $user->id) {
            return response()->json(['error' => 'You cannot delete yourself.'], 403);
        }
    
        $user->delete();
    
        return response()->json(['message' => 'User deleted successfully.']);
    }
    
    public function searchPatientsByName(Request $request)
    {
        $searchTerm = $request->input('query');
    
        $patients = Patient::whereHas('user', function ($query) use ($searchTerm) {
            if ($searchTerm) {
                $query->where('first_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
            }
        })->with('user:id,first_name,last_name')->get();
    
        $result = $patients->map(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->user->first_name . ' ' . $patient->user->last_name
            ];
        });
    
        return response()->json($result);
    }
    
    
    
    public function searchDoctorsByName(Request $request)
    {
        $searchTerm = $request->input('query');
    
        $doctors = Doctor::whereHas('user', function ($query) use ($searchTerm) {
            if ($searchTerm) {
                $query->where('first_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
            }
        })->with('user:id,first_name,last_name')->get();
    
        $result = $doctors->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->user->first_name . ' ' . $doctor->user->last_name
            ];
        });
    
        return response()->json($result);
    }
   

    public function topRatedDoctors($limit = 10)
    {
        $doctors = Doctor::with('user')
            ->withCount('ratings') // عدد التقييمات
            ->withAvg('ratings', 'rating') // متوسط التقييم
            ->orderByDesc('ratings_avg_rating') // ترتيب حسب المتوسط
            ->take($limit) // أعلى 10 بشكل افتراضي
            ->get();
    
        return response()->json($doctors);
    }
    


    
    
}
    

//example => (r=>read) index get | name: example.index
//example/{id} => (r=>read) get | name : example.show
//example/create => (c=>create) get | name:example.create
//example => (c=>create) =>post|name: example.store
//example/{id}/edit => (U=>update) get | name :example.edit
//example\{id} => (U=>Update) PUT method | name :example.update
//example/{id} => (D=>Delete) delete method |name =example.destroy

