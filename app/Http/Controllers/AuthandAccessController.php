<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Services\SafetikaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthandAccessController extends Controller
{
    protected $safetikaService;

    public function __construct(SafetikaService $safetikaService)
    {
        $this->safetikaService = $safetikaService;
    }
    /**
     * PROXY LOGIN (Password Grant Flow)
     * Takes credentials from Vue, validates them against the Safetika Hub,
     * creates a local shadow user, and issues a Sanctum token.
     */
    public function login(Request $request)
    {
        try {
            // 1. Validate request
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $hubConfig = config('services.safetika_hub');

            Log::info('Login attempt started', [
                'email' => $request->email,
                'hub_url' => $hubConfig['url'] ?? null,
            ]);

            if (empty($hubConfig['url']) || empty($hubConfig['client_id']) || empty($hubConfig['client_secret'])) {
                Log::error('Safetika hub authentication config is missing or incomplete', [
                    'hub_config' => $hubConfig,
                ]);

                return response()->json([
                    'message' => 'Authentication server is not configured. Please contact the administrator.',
                ], 500);
            }

            // 2. Request token from HUB
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post(rtrim($hubConfig['url'], '/').'/api/oauth/token', [
                    'grant_type' => 'password',
                    'client_id' => $hubConfig['client_id'],
                    'client_secret' => $hubConfig['client_secret'],
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ]);
                Log::info('HUB token response', [
    'status' => $response->status(),
    'body' => $response->json()
]);

            if ($response->failed()) {
                $hubError = $response->json();
                $clientMessage = $hubError['error_description'] ?? $hubError['message'] ?? 'Authentication failed';

                Log::error('HUB token request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'email' => $request->email,
                ]);

                return response()->json([
                    'message' => $clientMessage,
                    'error' => $hubError,
                ], 401);
            }

            $accessToken = $response->json()['access_token'] ?? null;

            if (! $accessToken) {
                Log::error('No access token returned from HUB', [
                    'response' => $response->json(),
                ]);

                return response()->json([
                    'message' => 'Invalid token response from auth server',
                ], 500);
            }

            // 3. Fetch user profile
            $userResponse = Http::withHeaders(['Accept' => 'application/json'])
                ->withToken($accessToken)
                ->get(rtrim($hubConfig['url'], '/').'/api/asset/me');

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

            Log::info('Response user data', [
                'body' => $responseData,
            ]);

            $hubUser = $responseData['user'] ?? $responseData;

            if (! $hubUser || ! isset($hubUser['email'])) {
                Log::error('Invalid user data from HUB', [
                    'response' => $responseData,
                ]);

                return response()->json([
                    'message' => 'Invalid user data from auth server',
                ], 500);
            }

            // 4. Process user data
            $fullName = trim(($hubUser['first_name'] ?? '').' '.($hubUser['last_name'] ?? ''));

            // ✅ FIXED ROLE HANDLING
            $hubRole = 'employee';

            if (! empty($hubUser['roles'])) {
                $firstRole = $hubUser['roles'][0];

                if (is_array($firstRole) && isset($firstRole['name'])) {
                    // Case: [{ name: "admin" }]
                    $hubRole = strtolower($firstRole['name']);
                } elseif (is_string($firstRole)) {
                    // Case: ["admin"]
                    $hubRole = strtolower($firstRole);
                }
            } elseif (! empty($hubUser['role'])) {
                $hubRole = strtolower($hubUser['role']);
            }

            Log::info('Resolved role', [
                'hub_roles_raw' => $hubUser['roles'] ?? null,
                'final_role' => $hubRole,
            ]);

            // Map to allowed roles
            $allowedLocalRoles = ['admin', 'manager', 'employee', 'management', 'hod'];
            $localRole = in_array($hubRole, $allowedLocalRoles) ? $hubRole : 'employee';

            // 5. Sync user locally
            $localUser = User::withTrashed()->updateOrCreate(
                ['email' => $hubUser['email']],
                [
                    'name' => $fullName ?: ($hubUser['name'] ?? 'Hub User'),
                    'password' => bcrypt(Str::random(24)), // dummy password
                    'role' => $localRole,
                    'is_active' => $hubUser['is_active'] ?? 1,
                    'is_verified' => 1,
                    'deleted_at' => null, // Ensure restored if soft-deleted
                ]
            );

           // Fetch departments from hub and sync (non-blocking)
try {
    $this->safetikaService->syncDepartments($accessToken);
} catch (\Exception $e) {
    Log::error('Department sync failed (non-blocking)', [
        'error' => $e->getMessage()
    ]);
}

            // Initialize variables to avoid undefined variable errors if the hub data is missing
            $firstDept = null;
            $deptId = null;
            $deptName = null;
            $departments = $hubUser['departments'] ?? [];

            if (!empty($departments)) {
                $firstDept = $departments[0];
                $deptId = $firstDept['id'] ?? null;
                $deptName = $firstDept['name'] ?? null;
            }
            // Check for department_name string
            elseif (isset($hubUser['department_name']) && is_string($hubUser['department_name'])) {
                $deptName = $hubUser['department_name'];
            }
            // Check for dept_name string
            elseif (isset($hubUser['dept_name']) && is_string($hubUser['dept_name'])) {
                $deptName = $hubUser['dept_name'];
            }
            // Check for dept string
            elseif (isset($hubUser['dept']) && is_string($hubUser['dept'])) {
                $deptName = $hubUser['dept'];
            }
            // Check for direct department_id field
            elseif (isset($hubUser['department_id'])) {
                $deptId = $hubUser['department_id'];
            }
            // Check for division
            elseif (isset($hubUser['division']) && is_string($hubUser['division'])) {
                $deptName = $hubUser['division'];
            }
            // Check for unit
            elseif (isset($hubUser['unit']) && is_string($hubUser['unit'])) {
                $deptName = $hubUser['unit'];
            }
            // Check for team
            elseif (isset($hubUser['team']) && is_string($hubUser['team'])) {
                $deptName = $hubUser['team'];
            }

          Log::info('Department extraction', [
    'raw_departments' => $departments,
    'selected_dept' => $firstDept,
    'deptId' => $deptId,
    'deptName' => $deptName,
            ]);

            // Find and assign department (FIXED)
$dept = null;

// Try by ID first
if ($deptId) {
    $dept = Department::withTrashed()->find($deptId);
    if ($dept && $dept->trashed()) {
        $dept->restore();
    }
}

// If NOT found by ID, fallback to name
if (!$dept && $deptName) {
    $dept = Department::withTrashed()->where('name', $deptName)->first();
    
    if ($dept) {
        $dept->restore();
        $dept->update(['description' => 'Synced from Safetika Hub']);
    } else {
        $dept = Department::create([
            'name' => $deptName,
            'description' => 'Synced from Safetika Hub'
        ]);
    }
}

// FINAL fallback (optional but VERY useful)
if (!$dept && $deptId) {
    $dept = Department::create([
        'name' => 'Department '.$deptId,
        'description' => 'Auto-created (ID fallback)'
    ]);
}

// Assign if we found or created something
if ($dept) {
    $localUser->department_id = $dept->id;
    $localUser->save();
}

// 🔍 Debug log (keep this while testing)
Log::info('Final department assignment', [
    'deptId_from_hub' => $deptId,
    'deptName_from_hub' => $deptName,
    'assigned_department' => $dept
]);

            // 6. Create Sanctum token
            $token = $localUser->createToken('ams_auth_token')->plainTextToken;

            // Load department for response
            $localUser->load('department:id,name');

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
        $response = Http::post(rtrim(config('services.safetika_hub.url'), '/').'/api/forgot-password', [
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
        $response = Http::post(rtrim(config('services.safetika_hub.url'), '/').'/api/reset-password', $request->all());

        return response()->json(
            $response->json(),
            $response->status()
        );
    }
}