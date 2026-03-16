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
        // Fetches all settings from the database as key => value
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        // backwards compatibility: frontend refers to "system_name" but earlier
        // versions used "site_name"; also map admin_email -> support_email when
        // appropriate.  we do not override if the new key already exists.
        if (isset($settings['site_name']) && !isset($settings['system_name'])) {
            $settings['system_name'] = $settings['site_name'];
        }
        if (isset($settings['admin_email']) && !isset($settings['support_email'])) {
            $settings['support_email'] = $settings['admin_email'];
        }

        // cast a few values back to their "natural" types so the front end
        // doesn't have to wrestle with strings where booleans or ints are
        // expected.
        foreach (['maintenance_mode', 'email_alerts', 'asset_movement_alerts'] as $boolKey) {
            if (isset($settings[$boolKey])) {
                $settings[$boolKey] = filter_var($settings[$boolKey], FILTER_VALIDATE_BOOLEAN);
            }
        }
        if (isset($settings['session_timeout'])) {
            $settings['session_timeout'] = (int) $settings['session_timeout'];
        }

        return response()->json($settings);
    }

    /**
     * Update multiple settings at once.
     */
    public function update(Request $request): JsonResponse
    {
        // only allow the fields that the UI currently binds to; we also keep the
        // old keys for legacy compatibility but they are not used by the new
        // front end.  booleans are accepted as nullable so we can turn things on
        // and off.
        $data = $request->validate([
            'system_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'currency' => 'nullable|string|max:10',
            'maintenance_mode' => 'nullable|boolean',
            'email_alerts' => 'nullable|boolean',
            'asset_movement_alerts' => 'nullable|boolean',
            'session_timeout' => 'nullable|integer|min:0',
            // keep the old keys just in case some external client still uses
            // them; they will be written but front end won’t read them any more.
            'site_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
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