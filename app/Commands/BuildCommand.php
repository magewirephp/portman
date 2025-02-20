<?php

declare(strict_types=1);

namespace App\Commands;

use App\Portman\Configuration\ConfigurationLoader;
use App\Portman\Configuration\Data\SourceComposer;
use App\Portman\SourceBuilder;
use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'build';

    protected $description = 'Build a codebase with Portman';

    public function handle(): void
    {
        $this->info('Building...');
        app(ConfigurationLoader::class)->setCommand($this);

        $validate = portman_config_data()->directories->validateSourceDirectories();
        if(is_string($validate)){
            $this->warn("Source directories {$validate} do not exist, attempting to download from composer");
            $this->runCommand('download-source',[], $this->output);
        }

        app(SourceBuilder::class)->build($this);
    }
}
