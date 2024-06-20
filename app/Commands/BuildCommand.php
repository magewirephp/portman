<?php
declare(strict_types=1);

namespace App\Commands;

use Illuminate\Console\Command;

class BuildCommand extends Command
{
    protected $signature = 'build';

    protected $description = 'Build a codebase with Poortman';

    public function handle(): void
    {
        $this->info('Building...');
        
    }
}
