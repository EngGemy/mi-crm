<?php

use App\Services\SettingsService;

if (! function_exists('settings')) {
    /**
     * Get a setting value or the SettingsService instance
     *
     * Usage:
     *   settings('company.name_ar')           // string value
     *   settings('contact.phones')            // array value
     *   settings()                            // SettingsService instance
     *   settings()->companyName()             // helper method
     *   settings()->primaryColor()            // #C00000
     */
    function settings(?string $key = null, $default = null): mixed
    {
        $service = app(SettingsService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key, $default);
    }
}
