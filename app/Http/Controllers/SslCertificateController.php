<?php

namespace App\Http\Controllers;

use App\Services\SslCertificateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SslCertificateController extends Controller
{
    protected SslCertificateService $service;

    public function __construct(SslCertificateService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'revocation_status', 'per_page']);
        return response()->json($this->service->paginated($filters));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'common_name'       => 'required|string',
            'expiry_date'       => 'required|date',
            'ca_vendor'         => 'nullable|string',
            'installed_on'      => 'nullable|string',
            'installed_on_type' => 'required|in:load_balancer,web_server,application,cdn,other',
            'assigned_owner_id' => 'nullable|exists:users,id',
            // Add other fields as necessary
        ]);

        $cert = $this->service->store($request->all());
        return response()->json($cert, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $cert = $this->service->update($id, $request->all());
        return response()->json($cert);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Certificate deleted']);
    }

    public function scan(int $id): JsonResponse
    {
        $cert = $this->service->scanCertificate($id);
        return response()->json($cert);
    }

    public function scanDomain(Request $request): JsonResponse
    {
        $request->validate([
            'host' => 'required|string',
            
        ]);

        $data = $this->service->scanDomain($request->host, $request->port ?? 443);
        return response()->json($data);
    }

    public function acknowledgeAlert(int $id): JsonResponse
    {
        $cert = $this->service->acknowledge30DayAlert($id);
        return response()->json($cert);
    }
}