<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafetikaService
{
    public function syncDepartments(?string $accessToken = null): void
    {
        $hubConfig = config('services.safetika_hub');

        if (empty($hubConfig['url'])) {
            return;
        }

        // If no token provided, try to get a system-level token using client credentials
        if (!$accessToken) {
            $accessToken = $this->getSystemToken();
        }

        if (!$accessToken) {
            Log::warning('Safetika department sync skipped: No access token available.');
            return;
        }

        try {
        // Try multiple potential endpoints
        $endpoints = [
            '/api/asset/departments',
            '/api/departments',
            '/api/v1/departments',
            '/api/v1/departments/all',
            '/api/asset/departments/all',
            '/api/resources/departments',
            '/api/all-departments',
            '/api/settings/departments',
        ];

            $hubDepartments = null;
            $successfulEndpoint = null;

            foreach ($endpoints as $endpoint) {
                Log::info('Trying department endpoint', ['endpoint' => $endpoint]);
                
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->withToken($accessToken)
                    ->timeout(10)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    // Check if response has data
                    if (!empty($data)) {
                        $hubDepartments = $data;
                        $successfulEndpoint = $endpoint;
                        Log::info('Found departments from endpoint', [
                            'endpoint' => $endpoint, 
                            'response' => $data
                        ]);
                        break;
                    }
                }
            }

        if (!$hubDepartments || (is_array($hubDepartments) && count($hubDepartments) === 0)) {
    Log::warning('No department data found in Hub response', ['response' => $hubDepartments]);
    return;
}
Log::info('Raw Hub Department Data', ['data' => $hubDepartments]);

            // Handle wrapped response - check for 'data' or 'departments' key
            if (isset($hubDepartments['data'])) {
                $hubDepartments = $hubDepartments['data'];
            } elseif (isset($hubDepartments['departments'])) {
                $hubDepartments = $hubDepartments['departments'];
            }

            // Clear old synced departments and replace with fresh ones
            $existingSyncDepts = Department::where('description', 'Synced from Safetika Hub')->pluck('name')->toArray();
            
            foreach ($hubDepartments as $hubDept) {
                if (!is_array($hubDept)) {
                    continue;
                }
                $deptId = $hubDept['id'] ?? null;
                $deptName = $hubDept['name'] ?? $hubDept['title'] ?? null;
                if ($deptName) {
                    try {
                        $dept = Department::updateOrCreate(
                            ['name' => $deptName],
                            ['description' => $hubDept['description'] ?? 'Synced from Safetika Hub']
                        );
                        Log::info('Department synced', ['id' => $dept->id, 'name' => $dept->name]);
                    } catch (\Exception $e) {
                        Log::warning('Department sync failed, skipping', ['name' => $deptName, 'error' => $e->getMessage()]);
                    }
                }
            }
            
            // Delete old departments that are no longer in hub
            Department::where('description', 'Synced from Safetika Hub')
                ->whereNotIn('name', array_column($hubDepartments, 'name'))
                ->delete();

            Log::info('Departments synced from Safetika Hub', [
                'count' => count($hubDepartments),
                'endpoint' => $successfulEndpoint,
                'departments' => array_column($hubDepartments, 'name')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync departments from hub', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function syncUsers(?string $accessToken = null): void
    {
        $hubConfig = config('services.safetika_hub');
        if (empty($hubConfig['url'])) return;

        if (!$accessToken) $accessToken = $this->getSystemToken();
        if (!$accessToken) return;

        try {
            $endpoints = [
                '/api/asset/users/all',
                '/api/asset/users',
                '/api/v1/users',
                '/api/users',
            ];

            $hubUsers = null;
            foreach ($endpoints as $endpoint) {
                $response = \Illuminate\Support\Facades\Http::withHeaders(['Accept' => 'application/json'])
                    ->withToken($accessToken)
                    ->timeout(15)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    $hubUsers = $data['data'] ?? $data['users'] ?? (is_array($data) && isset($data[0]) ? $data : null);
                    if ($hubUsers) break;
                }
            }

            if (!$hubUsers) return;

            foreach ($hubUsers as $hu) {
                if (!isset($hu['email'])) continue;
                
                $role = 'employee';
                $rawRole = $hu['role']['name'] ?? $hu['role'] ?? $hu['user_type'] ?? $hu['type'] ?? 'employee';
                if (is_string($rawRole)) $role = strtolower($rawRole);

                $deptId = null;
                $deptName = $hu['department']['name'] ?? $hu['department'] ?? $hu['department_name'] ?? null;
                if ($deptName) {
                    $deptId = \App\Models\Department::updateOrCreate(['name' => $deptName])->id;
                }

                \App\Models\User::updateOrCreate(
                    ['email' => $hu['email']],
                    [
                        'name' => $hu['name'] ?? trim(($hu['first_name'] ?? '') . ' ' . ($hu['last_name'] ?? '')),
                        'role' => in_array($role, ['admin', 'manager', 'employee', 'management', 'hod']) ? $role : 'employee',
                        'department_id' => $deptId,
                        'is_active' => $hu['is_active'] ?? 1,
                        'password' => $hu['password'] ?? \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24))
                    ]
                );
            }
            \Illuminate\Support\Facades\Log::info('Users synced from Safetika Hub', ['count' => count($hubUsers)]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync users from hub', ['error' => $e->getMessage()]);
        }
    }

    public function fetchUserRole(string $accessToken, ?int $userId = null): ?string
    {
        $hubConfig = config('services.safetika_hub');

        if (empty($hubConfig['url']) || !$userId) {
            return null;
        }

        try {
            $endpoints = [
                "/api/asset/users/{$userId}",
                "/api/asset/user/{$userId}",
                "/api/v1/users/{$userId}",
                "/api/v1/user/{$userId}",
                "/api/users/{$userId}",
            ];

            foreach ($endpoints as $endpoint) {
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->withToken($accessToken)
                    ->timeout(10)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                Log::info('fetchUserRole trying', ['endpoint' => $endpoint, 'status' => $response->status()]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Handle wrapped response
                    if (isset($data['user'])) {
                        $data = $data['user'];
                    } elseif (isset($data['data']) && is_array($data['data'])) {
                        $data = $data['data'];
                    }
                    
                    Log::info('fetchUserRole response', ['endpoint' => $endpoint, 'data_keys' => array_keys($data), 'data' => $data]);
                    
                    // Extract role from various response structures
                    $role = $data['role'] ?? $data['role_name'] ?? 
                            $data['user_type'] ?? $data['type'] ?? $data['access_level'] ?? 
                            ($data['technician_role'] ?? null);
                    
                    if ($role) {
                        Log::info('Found user role', ['endpoint' => $endpoint, 'role' => $role]);
                        return strtolower($role);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch user role', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function fetchUserFullData(string $accessToken, int $userId): ?array
    {
        $hubConfig = config('services.safetika_hub');

        if (empty($hubConfig['url'])) {
            return null;
        }

        try {
            // Try multiple endpoints for user data
            $endpoints = [
                "/api/asset/users/{$userId}",
                "/api/asset/user/{$userId}",
                "/api/v1/users/{$userId}",
                "/api/v1/user/{$userId}",
                "/api/users/{$userId}",
            ];

            foreach ($endpoints as $endpoint) {
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->withToken($accessToken)
                    ->timeout(10)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                Log::info('fetchUserFullData trying', ['endpoint' => $endpoint, 'status' => $response->status()]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Handle wrapped response
                    if (isset($data['user'])) {
                        $data = $data['user'];
                    } elseif (isset($data['data'])) {
                        $data = $data['data'];
                    }
                    
                    Log::info('fetchUserFullData response', ['endpoint' => $endpoint, 'data_keys' => array_keys($data)]);
                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch full user data', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function fetchUserDepartment(string $accessToken, int $userId): ?array
    {
        $hubConfig = config('services.safetika_hub');

        if (empty($hubConfig['url'])) {
            return null;
        }

        try {
            $endpoints = [
                "/api/asset/users/{$userId}",
                "/api/asset/user/{$userId}",
                "/api/v1/users/{$userId}",
                "/api/v1/user/{$userId}",
                "/api/users/{$userId}",
            ];

            foreach ($endpoints as $endpoint) {
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->withToken($accessToken)
                    ->timeout(10)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                Log::info('fetchUserDepartment trying', ['endpoint' => $endpoint, 'status' => $response->status()]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Handle wrapped response
                    if (isset($data['user'])) {
                        $data = $data['user'];
                    } elseif (isset($data['data']) && is_array($data['data'])) {
                        $data = $data['data'];
                    }
                    
                    Log::info('fetchUserDepartment response', ['endpoint' => $endpoint, 'data_keys' => array_keys($data), 'data' => $data]);
                    
                    // Check if response has department info
                    if (isset($data['department'])) {
                        if (is_array($data['department'])) {
                            return ['id' => $data['department']['id'] ?? null, 'name' => $data['department']['name'] ?? null];
                        }
                        return ['name' => $data['department']];
                    }
                    if (isset($data['department_id'])) {
                        return ['id' => $data['department_id'], 'name' => $data['department_name'] ?? null];
                    }
                    // Check for department name in various fields
                    foreach (['department_name', 'dept_name', 'dept', 'division', 'unit', 'team', 'section', 'section_name'] as $field) {
                        if (isset($data[$field]) && is_string($data[$field])) {
                            return ['name' => $data[$field]];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch user department', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Get a system-level access token using Client Credentials grant.
     */
    public function getSystemToken(): ?string
    {
        $hubConfig = config('services.safetika_hub');

        if (empty($hubConfig['url']) || empty($hubConfig['client_id']) || empty($hubConfig['client_secret'])) {
            return null;
        }

        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->asForm()
                ->post(rtrim($hubConfig['url'], '/') . '/api/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $hubConfig['client_id'],
                    'client_secret' => $hubConfig['client_secret'],
                    'scope' => '',
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            Log::error('Safetika Hub system token request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Safetika Hub system token exception', ['error' => $e->getMessage()]);
        }

        return null;
    }
}