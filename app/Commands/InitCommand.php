<?php

declare(strict_types=1);

namespace App\Commands;

use App\Portman\Configuration\ConfigurationLoader;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InitCommand extends Command
{
    protected $signature = 'init';

    protected $description = 'Create config file for Portman';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle()
    {
        app(ConfigurationLoader::class)->setCommand($this);
        if ($this->files->exists(ConfigurationLoader::DEFAULT_CONFIGURATION_FILE)) {
            $this->components->info('Config file [portman.config.php] is already present.');
            if (!$this->components->confirm('Would you like to overwrite [portman.config.php] with the default version?')) {
                return;
            }
        }
        $stub = $this->files->get($this->getStub());
        $this->files->put(ConfigurationLoader::DEFAULT_CONFIGURATION_FILE, $stub);
        $this->components->info('Config file [portman.config.php] created successfully.');
    }

    protected function getStub(): string
    {
        return realpath(__DIR__ . '/../../stubs/portman.config.php');
    }
}
