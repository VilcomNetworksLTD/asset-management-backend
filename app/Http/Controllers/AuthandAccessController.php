<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
            // 1. Request token from HUB
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post(rtrim(env('AUTH_HUB_URL'), '/').'/api/oauth/token', [
                    'grant_type' => 'password',
                    'client_id' => env('AUTH_HUB_PASSWORD_CLIENT_ID'),
                    'client_secret' => env('AUTH_HUB_PASSWORD_CLIENT_SECRET'),
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ]);


           

            if ($response->failed()) {
                Log::error('HUB token request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'email' => $request->email,
                ]);

                return response()->json([
                    'message' => 'Authentication failed',
                    'error' => $response->json(),
                ], 401);
            }

            $accessToken = $response->json()['access_token'] ?? null;

            if (! $accessToken) {
              
                return response()->json([
                    'message' => 'Invalid token response from auth server',
                ], 500);
            }

            // 2. Fetch user profile
            $userResponse = Http::withHeaders(['Accept' => 'application/json'])
                ->withToken($accessToken)
                ->get(rtrim(env('AUTH_HUB_URL'), '/').'/api/asset/me');

            if ($userResponse->failed()) {
                Log::error('Failed to fetch profile from HUB', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);

                return response()->json([
                    'message' => 'Failed to fetch user profile',
                ], 500);
            }

            $responseData = $userResponse->json();
            $hubUser = $responseData['user'] ?? $responseData;

            if (! $hubUser || ! isset($hubUser['email'])) {
                Log::error('Invalid user data from HUB', [
                    'response' => $responseData,
                ]);

                return response()->json([
                    'message' => 'Invalid user data from auth server',
                ], 500);
            }

            // 3. Process user data
            $fullName = trim(($hubUser['first_name'] ?? '').' '.($hubUser['last_name'] ?? ''));
            $hubRole = strtolower($hubUser['roles'][0]['name'] ?? $hubUser['role'] ?? 'employee');

            $allowedLocalRoles = ['admin', 'manager', 'employee'];
            $localRole = in_array($hubRole, $allowedLocalRoles) ? $hubRole : 'employee';

            // 4. Sync user
            $localUser = User::updateOrCreate(
                ['email' => $hubUser['email']],
                [
                    'name' => $fullName ?: ($hubUser['name'] ?? 'Hub User'),
                    'password' => bcrypt(Str::random(24)),
                    'role' => $localRole,
                    'is_active' => $hubUser['is_active'] ?? 1,
                ]
            );

            // 5. Create token
            $token = $localUser->createToken('ams_auth_token')->plainTextToken;

        

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $localUser,
            ]);

        } catch (\Exception $e) {
            Log::error('Login process crashed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Something went wrong during login',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'message' => 'Account creation is managed centrally. Please contact your administrator to create a Safetika Hub account.',
        ], 403);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Proxy this request to the Safetika Hub's forgot-password API endpoint
        $response = Http::post(rtrim(env('AUTH_HUB_URL'), '/').'/api/forgot-password', [
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
        $response = Http::post(rtrim(env('AUTH_HUB_URL'), '/').'/api/reset-password', $request->all());

        return response()->json(
            $response->json(),
            $response->status()
        );
    }
}
