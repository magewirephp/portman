<?php

namespace App\Portman;

use Spatie\Watcher\Exceptions\CouldNotStartWatcher;
use Spatie\Watcher\Watch as SpatieWatch;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Watch extends SpatieWatch
{
    const EVENT_TYPE_FILE_CREATED = 'add';

    const EVENT_TYPE_FILE_UPDATED = 'change';

    const EVENT_TYPE_FILE_DELETED = 'unlink';

    const EVENT_TYPE_DIRECTORY_CREATED = 'addDir';

    const EVENT_TYPE_DIRECTORY_DELETED = 'unlinkDir';

    public static function path(string $path): SpatieWatch
    {
        return (new self())->setPaths($path);
    }

    public static function paths(...$paths): SpatieWatch
    {
        return (new self())->setPaths($paths);
    }

    protected function getWatchProcess(): Process
    {
        $command = [
            (new ExecutableFinder)->find('chokidar'),
            ...$this->paths,
        ];
        $process = new Process(
            command: $command,
            timeout: null,
        );
        $process->start();

        return $process;
    }

    public function start(): void
    {
        $watcher = $this->getWatchProcess();
        while (true) {
            if (!$watcher->isRunning()) {
                throw CouldNotStartWatcher::make($watcher);
            }

            if ($output = $watcher->getIncrementalOutput()) {
                $this->actOnOutput($output);
            }

            if (!($this->shouldContinue)()) {
                break;
            }

            usleep($this->interval);
        }
    }

    protected function actOnOutput(string $output): void
    {
        $lines = explode(PHP_EOL, $output);

        $lines = array_filter($lines);

        foreach ($lines as $line) {
            [$type, $path] = explode(':', $line, 2);
            $path = trim($path);

            match ($type) {
                static::EVENT_TYPE_FILE_CREATED => $this->callAll($this->onFileCreated, $path),
                static::EVENT_TYPE_FILE_UPDATED => $this->callAll($this->onFileUpdated, $path),
                static::EVENT_TYPE_FILE_DELETED => $this->callAll($this->onFileDeleted, $path),
                static::EVENT_TYPE_DIRECTORY_CREATED => $this->callAll($this->onDirectoryCreated, $path),
                static::EVENT_TYPE_DIRECTORY_DELETED => $this->callAll($this->onDirectoryDeleted, $path),
                default => null
            };

            foreach ($this->onAny as $onAnyCallable) {
                $onAnyCallable($type, $path);
            }
        }
    }
}
