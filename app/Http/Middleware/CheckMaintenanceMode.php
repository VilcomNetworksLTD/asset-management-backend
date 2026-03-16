<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * Block non-admin users when maintenance_mode setting is truthy.
     */
    public function handle(Request $request, Closure $next)
    {
        $mode = Setting::where('key', 'maintenance_mode')->value('value');
        $active = filter_var($mode, FILTER_VALIDATE_BOOLEAN);

        if ($active && auth()->check() && auth()->user()->role !== 'admin') {
            // if we're already returning a JSON response (API) then 503 makes sense
            return response()->json(['message' => 'The system is under maintenance. Please try again later.'], 503);
        }

        return $next($request);
    }
}
