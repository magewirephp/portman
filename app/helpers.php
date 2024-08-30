<?php

use App\Portman\Configuration;

if (!function_exists('portman_config')) {
    function portman_config(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return app(Configuration::class)->all();
        }

        return app(Configuration::class)->get($key, $default);
    }
}
