<?php

declare(strict_types=1);

namespace App\Commands;

use App\Poortman\SourceBuilder;
use App\Poortman\Watch;
use Illuminate\Console\Command;

class WatchCommand extends Command
{
    protected $signature = 'watch';

    protected $description = 'Watch a codebase with Poortman';

    public function handle(): void
    {
        $paths = [
            ...poortman_config('directories.source', []),
            ...poortman_config('directories.augmentation', []),
            ...poortman_config('directories.addition', []),
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
