<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsPatient
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'patient') {
            return response()->json(['error' => 'Access denied. Not a patient.'], 403);
        }

        return $next($request);
    }
}
