<?php

declare(strict_types=1);

namespace App\Commands;

use App\Poortman\Configuration;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InitCommand extends Command
{
    protected $signature = 'init';

    protected $description = 'Create config file for Poortman';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->files->exists(Configuration::DEFAULT_CONFIGURATION_FILE)) {
            $this->components->info('Config file [poortman.config.php] is already present.');
            if (! $this->components->confirm('Would you like to overwrite [poortman.config.php] with the default version?')) {
                return;
            }
        }
        $stub = $this->files->get($this->getStub());
        $this->files->put(Configuration::DEFAULT_CONFIGURATION_FILE, $stub);
        $this->components->info('Config file [poortman.config.php] created successfully.');
    }

    protected function getStub(): string
    {
        return realpath(__DIR__.'/../../stubs/poortman.config.php');
    }
}
