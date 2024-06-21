<?php
declare(strict_types=1);

namespace App\Commands;

use App\Poortman\SourceBuilder;
use Illuminate\Console\Command;
use Spatie\Watcher\Watch;

class WatchCommand extends Command
{
    protected $signature = 'watch';

    protected $description = 'Watch a codebase with Poortman';

    public function handle(): void
    {
        $this->info('Watching...');

        $sourceBuilder = app(SourceBuilder::class);
        Watch::paths(
            ...poortman_config('source-directories', []),
            ...poortman_config('augmentation-directories', []),
            ...poortman_config('addition-directories', []),
        )
            ->onAnyChange(function (string $type, string $path) use ($sourceBuilder) {
                if (in_array($type, [Watch::EVENT_TYPE_FILE_CREATED, Watch::EVENT_TYPE_FILE_UPDATED])) {
                    $sourceBuilder->buildFile(
                        $path,
                        $_ENV['SOURCE_DIR'],
                        $_ENV['AUGMENTIONS_DIR'],
                        $_ENV['ADDITIONS_DIR'],
                        $_ENV['DIST_DIR']
                    );
                }
            })
            ->start();

    }
}