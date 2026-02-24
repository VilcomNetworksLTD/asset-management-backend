<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    /**
     * Get a specific setting value by key.
     */
    public function get($key, $default = null)
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Update or create a setting record.
     */
    public function updateSetting($key, $value)
    {
        return Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}