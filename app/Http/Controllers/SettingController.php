<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get all settings as a key-value pair.
     */
    public function index(): JsonResponse
    {
        // Fetches all settings from the database
        $settings = \App\Models\Setting::pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Update multiple settings at once.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email|max:255',
            'support_email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            'date_format' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:100',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
        ]);

        foreach ($data as $key => $value) {
            $this->settingService->updateSetting($key, $value);
        }

        return response()->json([
            'message' => 'Settings updated successfully!',
            'settings' => \App\Models\Setting::pluck('value', 'key'),
        ]);
    }
}