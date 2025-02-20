<?php

namespace App\Portman\Configuration;

use App\Portman\Configuration\Data\Configuration;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ConfigurationLoader
{
    public const DEFAULT_CONFIGURATION_FILE = 'portman.config.php';

    private ?array              $config     = null;
    private ?Data\Configuration $configData = null;
    private ?Command            $command    = null;

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

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

        $config           = Storage::get($configFilePath);
        $configArray      = require realpath(Storage::path($configFilePath));
        if (!is_array($configArray)) {
            $message = 'The "' . $configFilePath . '" configuration file does not return an array, and is therefor not valid.';
            Log::error($message);
            throw new ConfigurationFileException($message);
        }
        try {
            $this->configData = Data\Configuration::from($configArray);
            $this->config     = $this->configData->toArray();
            Data\Configuration::validate($this->config);
        }
        catch (ValidationException $e) {
            $errors = collect($e->validator->getMessageBag()->getMessages())
                ->map(fn($messages, $key) => [$key, join(' ', $messages)])
                ->toArray();
            $this->command?->error('The "' . $configFilePath . '" configuration file is not valid:');
            $this->command?->table(['Location', 'Error'], $errors, 'borderless');
            throw new ConfigurationFileException('The "' . $configFilePath . '" configuration file is not valid:');
        }
    }

    public function getData():Configuration
    {
        if (is_null($this->config)) {
            $this->loadConfiguration();
        }

        return $this->configData;
    }
    public function get(string $key, $default = null)
    {
        if (is_null($this->config)) {
            $this->loadConfiguration();
        }

        return Arr::get($this->config, $key, $default);
    }
}
