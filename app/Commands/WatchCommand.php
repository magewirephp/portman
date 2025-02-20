<?php

declare(strict_types=1);

namespace App\Commands;

use App\Portman\Configuration\ConfigurationLoader;
use App\Portman\SourceBuilder;
use App\Portman\Watch;
use Illuminate\Console\Command;

class WatchCommand extends Command
{
    protected $signature = 'watch';

    protected $description = 'Watch a codebase with Portman';

    public function handle(): void
    {
        app(ConfigurationLoader::class)->setCommand($this);
        $configDirectories = portman_config_data()->directories;

        $validate = $configDirectories->validateSourceDirectories();
        if(is_string($validate)){
            $this->warn("Source directories {$validate} do not exist, attempting to download from composer");
            $this->runCommand('download-source',[], $this->output);
        }

        $paths = [
            ...$configDirectories->getSourcePaths(),
            ...$configDirectories->getAugmentationPaths(),
            ...$configDirectories->getAdditionalPaths(),
        ];

        $this->info('Watching... [' . implode(', ', $paths) . ']');

        $sourceBuilder = app(SourceBuilder::class);
        Watch::paths(...$paths)
            ->onAnyChange(function (string $type, string $path) use ($sourceBuilder) {
                if (in_array($type, [Watch::EVENT_TYPE_FILE_CREATED, Watch::EVENT_TYPE_FILE_UPDATED])) {
                    $sourceBuilder->buildFile($path, $this);
                }
            })
            ->start();

    }
}
