<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Services\SafetikaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

            Log::info('Raw hubUser keys', ['keys' => array_keys($hubUser ?? [])]);

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

            // Fetch additional user data from Hub API (role, department, etc.)
            $fullUserData = $this->safetikaService->fetchUserFullData($accessToken, $hubUser['id'] ?? 0);
            if ($fullUserData) {
                $hubUser = array_merge($hubUser, $fullUserData);
                Log::info('Merged full user data', ['keys' => array_keys($hubUser)]);
            }

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
            } elseif (! empty($hubUser['technician_role'])) {
                $hubRole = strtolower($hubUser['technician_role']);
            } elseif (! empty($hubUser['user_type'])) {
                $hubRole = strtolower($hubUser['user_type']);
            } elseif (! empty($hubUser['type'])) {
                $hubRole = strtolower($hubUser['type']);
            } elseif (! empty($hubUser['access_level'])) {
                $hubRole = strtolower($hubUser['access_level']);
            } elseif (! empty($hubUser['role_name'])) {
                $hubRole = strtolower($hubUser['role_name']);
            } elseif (! empty($hubUser['user_role'])) {
                $hubRole = strtolower($hubUser['user_role']);
            }

            Log::info('Role from hubUser', ['hubRole' => $hubRole, 'fields_checked' => ['roles', 'role', 'technician_role', 'user_type', 'type', 'access_level', 'role_name', 'user_role']]);

            // Try to fetch roles from Hub API if available
            $hubRole = $this->safetikaService->fetchUserRole($accessToken, $hubUser['id'] ?? null) ?? $hubRole;

            Log::info('Resolved role', [
                'hub_roles_raw' => $hubUser['roles'] ?? $hubUser['role'] ?? null,
                'hub_user_keys' => array_keys($hubUser ?? []),
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

            // Fetch departments from hub and sync
            $this->safetikaService->syncDepartments($accessToken);

// Try various ways Safetika might return department info
            $deptId = null;
            $deptName = null;
            $departments = $hubUser['departments'] ?? [];

            // The hubUser now contains merged data from fetchUserFullData
            // Check all possible department fields
            $deptFields = [
                'department', 'department_id', 'department_name', 'dept_name', 'dept',
                'division', 'unit', 'team', 'section', 'section_name', 'job_title', 'site'
            ];
            
            foreach ($deptFields as $field) {
                if (isset($hubUser[$field])) {
                    $value = $hubUser[$field];
                    if (is_array($value)) {
                        // Nested: { department: { id: 1, name: "CSS" } }
                        $deptId = $value['id'] ?? null;
                        $deptName = $value['name'] ?? $value['title'] ?? null;
                    } elseif (is_numeric($value)) {
                        // Numeric ID
                        $deptId = $value;
                    } elseif (is_string($value)) {
                        // String name
                        $deptName = $value;
                    }
                    if ($deptId || $deptName) {
                        Log::info('Department found from field', ['field' => $field, 'deptId' => $deptId, 'deptName' => $deptName]);
                        break;
                    }
                }
            }

            // Fallback: try fetchUserDepartment API if still not found
            if (!$deptId && !$deptName) {
                $userDeptData = $this->safetikaService->fetchUserDepartment($accessToken, $hubUser['id'] ?? 0);
                if ($userDeptData) {
                    $deptId = $userDeptData['id'] ?? null;
                    $deptName = $userDeptData['name'] ?? null;
                    Log::info('Department from API fallback', ['deptId' => $deptId, 'deptName' => $deptName]);
                }
            }

            Log::info('Looking for department', [
                'deptId' => $deptId,
                'deptName' => $deptName,
                'hubUser_fields' => array_keys($hubUser)
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

    public function debugHub(): JsonResponse
    {
        $hubConfig = config('services.safetika_hub');
        
        $result = [
            'configured' => !empty($hubConfig['url']),
            'url' => $hubConfig['url'] ?? null,
            'token_status' => null,
            'user_data' => null,
            'error' => null,
        ];

        try {
            $tokenResponse = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post(rtrim($hubConfig['url'], '/').'/api/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $hubConfig['client_id'],
                    'client_secret' => $hubConfig['client_secret'],
                    'scope' => '',
                ]);

            $result['token_status'] = $tokenResponse->status();
            
            if ($tokenResponse->successful()) {
                $accessToken = $tokenResponse->json()['access_token'] ?? null;
                
                if ($accessToken) {
                    $userResponse = Http::withHeaders(['Accept' => 'application/json'])
                        ->withToken($accessToken)
                        ->get(rtrim($hubConfig['url'], '/').'/api/asset/me');
                    
                    $result['user_data'] = $userResponse->json();
                }
            } else {
                $result['error'] = $tokenResponse->json();
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return response()->json($result);
    }
}