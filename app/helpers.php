<?php

use App\Poortman\Configuration;

if (! function_exists('poortman_config')) {
    function poortman_config(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return app(Configuration::class)->all();
        }

        return app(Configuration::class)->get($key, $default);
    }
}
