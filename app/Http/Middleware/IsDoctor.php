<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsDoctor
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return response()->json(['error' => 'Access denied. Not a doctor.'], 403);
        }

        return $next($request);
    }
}

