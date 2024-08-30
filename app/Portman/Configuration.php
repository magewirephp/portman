<?php

namespace App\Portman;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Configuration
{
    public const DEFAULT_CONFIGURATION_FILE = 'portman.config.php';

    private ?array $config = null;

    public function all(): array
    {
        if (is_null($this->config)) {
            $this->loadConfiguration();
        }

        return $this->config;
    }

    protected function loadConfiguration(): void
    {
        $defaultconfigPath = 'portman.config.php';
        $configFilePath    = env('PORTMAN_CONFIG_FILE', self::DEFAULT_CONFIGURATION_FILE);
        if (!Storage::has($configFilePath)) {
            $message = 'Configuration file "' . $configFilePath . '" does not exists. ';
            if ($configFilePath === self::DEFAULT_CONFIGURATION_FILE) {
                $message .= 'You can create the file with the `php portman init` command.';
            }
            else {
                $message .= 'Correct the `PORTMAN_CONFIG_FILE` environment variable to point to the configuration file.';
            }
            Log::error($message);
            throw new ConfigurationFileException($message);
        }

        $config       = Storage::get($configFilePath);
        $this->config = require realpath(Storage::path($configFilePath));

        if (!is_array($this->config)) {
            $message = 'The "' . $configFilePath . '" configuration file does not return an array, and is therefor not valid.';
            Log::error($message);
            throw new ConfigurationFileException($message);
        }
    }

    public function get(string $key, $default = null)
    {
        if (is_null($this->config)) {
            $this->loadConfiguration();
        }

        return Arr::get($this->config, $key, $default);
    }
}
