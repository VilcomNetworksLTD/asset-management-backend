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

        try {
            if (!$accessToken) {
                $accessToken = $this->getAccessToken($hubConfig);
            }

            $tokenForRequest = $accessToken;

            // Try multiple potential endpoints
            $endpoints = [
                '/api/settings/departments',
            ];

            $hubDepartments = null;
            $successfulEndpoint = null;

            foreach ($endpoints as $endpoint) {
                Log::info('Trying department endpoint', ['endpoint' => $endpoint, 'has_token' => !empty($tokenForRequest)]);

                $request = Http::withHeaders(['Accept' => 'application/json']);

                if ($tokenForRequest) {
                    $request = $request->withToken($tokenForRequest);
                }

                $response = $request
                    ->timeout(10)
                    ->get(rtrim($hubConfig['url'], '/') . $endpoint);

                if ($response->successful()) {
                    $hubDepartments = $response->json();
                    $successfulEndpoint = $endpoint;
                    Log::info('Found departments from endpoint', [
                        'endpoint' => $endpoint,
                        'response' => $hubDepartments,
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

            // Clear old synced departments and replace with fresh ones (Commented out to avoid race conditions)
            // Department::where('description', 'Synced from Safetika Hub')->delete();

            foreach ($hubDepartments as $hubDept) {
                $deptName = is_array($hubDept) ? ($hubDept['name'] ?? $hubDept['title'] ?? null) : $hubDept;

                if ($deptName) {
                    $deptName = trim($deptName);
                    Department::updateOrCreate(
                        ['name' => $deptName],
                        ['description' => $hubDept['description'] ?? 'Synced from Safetika Hub']
                    );
                }
            }

            Log::info('Departments synced from Safetika Hub', [
                'count' => count($hubDepartments),
                'endpoint' => $successfulEndpoint,
                'departments' => array_column($hubDepartments, 'name'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync departments from hub', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getAccessToken(array $hubConfig): ?string
    {
        if (empty($hubConfig['url']) || empty($hubConfig['client_id']) || empty($hubConfig['client_secret'])) {
            Log::error('Safetika Hub config incomplete for token request');
            return null;
        }

        try {
            $response = Http::asForm()
                ->post(rtrim($hubConfig['url'], '/') . '/api/oauth/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $hubConfig['client_id'],
                    'client_secret' => $hubConfig['client_secret'],
                    'scope' => '',
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            Log::error('Failed to get Safetika Hub client token', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Safetika Hub token request crashed', ['error' => $e->getMessage()]);
        }

        return null;
    }

}