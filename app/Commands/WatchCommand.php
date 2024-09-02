<?php

declare(strict_types=1);

namespace App\Commands;

use App\Portman\SourceBuilder;
use App\Portman\Watch;
use Illuminate\Console\Command;

class WatchCommand extends Command
{
    protected $signature = 'watch';

    protected $description = 'Watch a codebase with Portman';

    public function handle(): void
    {
        $paths = [
            ...portman_config('directories.source', []),
            ...portman_config('directories.augmentation', []),
            ...portman_config('directories.additional', []),
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
