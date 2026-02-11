<?php

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Auth;

if (! function_exists('settings')) {
    /**
     * Get an application setting value.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function settings($key = null, $default = null)
    {
        if (! Auth::check()) {
            return $default;
        }

        $userId = Auth::id();

        // Simple request-level caching
        static $settings = null;

        if ($settings === null) {
            $settings = ApplicationSetting::pluck('value', 'key')
                ->toArray();
        }

        if (is_null($key)) {
            return $settings;
        }

        return $settings[$key] ?? $default;
    }
}
