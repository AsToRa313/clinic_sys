<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsReceptionist
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'receptionist') {
            return response()->json(['error' => 'Access denied. Not a receptionist.'], 403);
        }

        return $next($request);
    }
}

