<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthandAccessController extends Controller
{
    /**
     * AUTH 2 LOGIN (Direct Database Flow)
     * Validates credentials against the Safetika database,
     * syncs a local shadow user, and issues a Sanctum token.
     */
    public function login(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Login attempt', [
            'email' => $request->email,
            'has_password' => $request->has('password')
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 1. Find user in Safetika database (match by email or username)
        $hubUser = DB::connection('safetika')
            ->table('users')
            ->where('email', $request->email)
            ->orWhere('username', $request->email)
            ->first();


        if (!$hubUser) {
            \Illuminate\Support\Facades\Log::warning('User not found in Safetika', ['email' => $request->email]);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        \Illuminate\Support\Facades\Log::info('User found in Safetika', ['id' => $hubUser->id]);

        // 2. Verify password
        if (!Hash::check($request->password, $hubUser->password)) {
            \Illuminate\Support\Facades\Log::warning('Password mismatch for user', ['email' => $request->email]);
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }


        // 3. Fetch roles from Safetika (using Spatie model_has_roles)
        $hubRoles = DB::connection('safetika')
            ->table('roles')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $hubUser->id)
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->pluck('roles.name')
            ->toArray();


        $hubRole = strtolower($hubRoles[0] ?? 'employee');

        // Define the strict roles your local Asset App database allows
        $allowedLocalRoles = ['admin', 'manager', 'employee']; 
        
        // Map roles: if Hub role is 'superadmin' or 'admin', use 'admin'
        if (in_array($hubRole, ['superadmin', 'admin'])) {
            $localRole = 'admin';
        } elseif (in_array($hubRole, $allowedLocalRoles)) {
            $localRole = $hubRole;
        } else {
            $localRole = 'employee';
        }


        // 4. Extract data (Safetika uses first_name and last_name)
        $fullName = trim(($hubUser->first_name ?? '') . ' ' . ($hubUser->last_name ?? ''));

        // 5. Sync the local Shadow User
        $localUser = User::updateOrCreate(
            ['email' => $hubUser->email],
            [
                'name' => $fullName ?: ($hubUser->username ?? 'Hub User'),
                'password' => bcrypt(Str::random(24)), // Secure dummy password
                'role' => $localRole,
                'is_verified' => $hubUser->email_verified_at ? 1 : 0,
            ]
        );

        // 6. Issue a local Sanctum Token for the Vue app
        $token = $localUser->createToken('ams_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $localUser
        ]);
    }

    /**
     * LOGOUT
     * Revokes the local Sanctum token so the Vue app forgets the user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /* --------------------------------------------------------------------------
     * CENTRAL IDENTITY OVERRIDES
     * Because Safetika Hub manages identity, the Asset App should no longer 
     * handle local registrations or password resets. We proxy these to the Hub 
     * or return a message directing the frontend to use the Hub API.
     * -------------------------------------------------------------------------- */

    public function register(Request $request)
    {
        return response()->json([
            'message' => 'Account creation is managed centrally. Please contact your administrator to create a Safetika Hub account.'
        ], 403);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Check if user exists in Safetika directly
        $exists = DB::connection('safetika')->table('users')->where('email', $request->email)->exists();
        if (!$exists) {
            return response()->json(['message' => 'User not found in Safetika database.'], 404);
        }

// ... existing code ...
        try {
            // Attempt to proxy to Hub for actual email delivery
            $response = Http::timeout(30)->post(rtrim(env('AUTH_HUB_URL'), '/') . '/api/forgot-password', [
                'email' => $request->email,
            ]);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'The Safetika Hub service is currently unavailable. Please contact your administrator to reset your password.'
            ], 503);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        try {
            // Proxy this request to the Safetika Hub's reset-password API endpoint
            $response = Http::timeout(30)->post(rtrim(env('AUTH_HUB_URL'), '/') . '/api/reset-password', $request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'The Safetika Hub service is currently unavailable. Please try again later.'
            ], 503);
        }
    }
}