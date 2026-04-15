<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafetikaService
{
    public function syncDepartments(): void
    {
        $hubConfig = config('services.safetika_hub');

        if (empty($hubConfig['url'])) {
            return;
        }

        try {
            // Try multiple potential endpoints
            $endpoints = [
                '/api/asset/departments',
                '/api/departments',
                '/api/v1/departments',
            ];

            $hubDepartments = null;
            $successfulEndpoint = null;

            foreach ($endpoints as $endpoint) {
                Log::info('Trying department endpoint', ['endpoint' => $endpoint]);
                
                $response = Http::withHeaders(['Accept' => 'application/json'])
                    ->timeout(10)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                if ($response->successful()) {
                    $hubDepartments = $response->json();
                    $successfulEndpoint = $endpoint;
                    Log::info('Found departments from endpoint', [
                        'endpoint' => $endpoint, 
                        'response' => $hubDepartments
                    ]);
                    break;
                }
            }

            if (!$hubDepartments) {
                Log::warning('No department endpoints returned data', ['tried' => $endpoints]);
                return;
            }

            // Handle wrappted response
            if (isset($hubDepartments['data'])) {
                $hubDepartments = $hubDepartments['data'];
            }

            // Clear old synced departments and replace with fresh ones
            Department::where('description', 'Synced from Safetika Hub')->delete();

            foreach ($hubDepartments as $hubDept) {
                $deptName = is_array($hubDept) ? ($hubDept['name'] ?? $hubDept['title'] ?? null) : $hubDept;
                if ($deptName) {
                    Department::firstOrCreate(
                        ['name' => $deptName],
                        ['description' => $hubDept['description'] ?? 'Synced from Safetika Hub']
                    );
                }
            }

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
}