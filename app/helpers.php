<?php

use App\Portman\Configuration\ConfigurationLoader;
use App\Portman\Configuration\Data\Configuration;

if (!function_exists('portman_config')) {
    function portman_config(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return app(ConfigurationLoader::class)->all();
        }

        return app(ConfigurationLoader::class)->get($key, $default);
    }
}
if (!function_exists('portman_config_data')) {
    function portman_config_data(): Configuration
    {
        return app(ConfigurationLoader::class)->getData();
    }
}


if (!function_exists('ensure_dir')) {
    function ensure_dir(string $path,int $levels = 1): string
    {
        if ($levels > 0) {
            $path = dirname($path);
        }
        if (!file_exists($path)) {
            mkdir($path, recursive: true);
        }

        return $path;
    }
}