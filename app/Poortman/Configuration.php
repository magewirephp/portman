<?php

namespace App\Poortman;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Configuration
{
    public const DEFAULT_CONFIGURATION_FILE = 'poortman.config.php';
    private ?array $config = null;

    public function all(): array
    {
        if (is_null($this->config)) {
            $this->loadConfiguration();
        }

        return $this->config;
    }

    public function get(string $key, $default = null)
    {
        if (is_null($this->config)) {
            $this->loadConfiguration();
        }

        return $this->config[$key] ?? $default;
    }

    protected function loadConfiguration(): void
    {
        $defaultconfigPath = 'poortman.config.php';
        $configFilePath    = env('POORTMAN_CONFIG_FILE', self::DEFAULT_CONFIGURATION_FILE);
        if (!Storage::has($configFilePath)) {
            $message = 'Configuration file "' . $configFilePath . '" does not exists. ';
            if ($configFilePath === self::DEFAULT_CONFIGURATION_FILE) {
                $message .= "You can create the file with the `php poortman init` command.";
            }
            else {
                $message .= 'Correct the `POORTMAN_CONFIG_FILE` environment variable to point to the configuration file.';
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
}