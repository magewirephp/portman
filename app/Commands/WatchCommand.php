<?php
declare(strict_types=1);

namespace App\Commands;

use Illuminate\Console\Command;

class WatchCommand extends Command
{
    protected $signature = 'watch';

    protected $description = 'Watch a codebase with Poortman';

    public function handle(): void
    {
        $this->info('Watching...');

    }
}