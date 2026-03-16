<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthandAccessController extends Controller
{
    /**
     * PROXY LOGIN (Password Grant Flow)
     * Takes credentials from Vue, validates them against the Safetika Hub, 
     * creates a local shadow user, and issues a Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 1. Ask the Hub if the password is correct
        $response = Http::asForm()->post(rtrim(env('AUTH_HUB_URL'), '/') . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => env('AUTH_HUB_PASSWORD_CLIENT_ID'),
            'client_secret' => env('AUTH_HUB_PASSWORD_CLIENT_SECRET'),
            'username' => $request->email,
            'password' => $request->password,
            'scope' => '',
        ]);

        if ($response->failed()) {
            // This forces the Asset App to pass the Hub's exact error straight to your Vue screen!
            return response()->json([
                'message' => 'HUB ERROR: ' . json_encode($response->json()) . ' | Status: ' . $response->status()
            ], 401);
        }

        $accessToken = $response->json()['access_token'];

        // 2. Fetch the user's profile from the Hub
        $userResponse = Http::withToken($accessToken)->get(rtrim(env('AUTH_HUB_URL'), '/') . '/api/user');

        if ($userResponse->failed()) {
            return response()->json(['message' => 'Failed to fetch profile from Hub.'], 500);
        }

        $hubUser = $userResponse->json();

        // 3. Extract data and apply the Role Translation Fix
        $fullName = trim(($hubUser['first_name'] ?? '') . ' ' . ($hubUser['last_name'] ?? ''));
        $hubRole = strtolower($hubUser['roles'][0]['name'] ?? $hubUser['role'] ?? 'employee');
        
        // Define the strict roles your local Asset App database allows
        $allowedLocalRoles = ['admin', 'manager', 'employee']; 
        
        // If Hub says 'technician', it defaults to 'employee' so MySQL doesn't crash
        $localRole = in_array($hubRole, $allowedLocalRoles) ? $hubRole : 'employee';

        // 4. Sync the local Shadow User
        $localUser = User::updateOrCreate(
            ['email' => $hubUser['email']],
            [
                'name' => $fullName ?: ($hubUser['name'] ?? 'Hub User'),
                'password' => bcrypt(Str::random(24)), // Secure dummy password
                'role' => $localRole,
                'is_active' => $hubUser['is_active'] ?? 1,
            ]
        );

        // 5. Issue a local Sanctum Token for the Vue app
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

        // Proxy this request to the Safetika Hub's forgot-password API endpoint
        $response = Http::post(rtrim(env('AUTH_HUB_URL'), '/') . '/api/forgot-password', [
            'email' => $request->email,
        ]);

        return response()->json(
            $response->json(), 
            $response->status()
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        // Proxy this request to the Safetika Hub's reset-password API endpoint
        $response = Http::post(rtrim(env('AUTH_HUB_URL'), '/') . '/api/reset-password', $request->all());

        return response()->json(
            $response->json(), 
            $response->status()
        );
    }
}