<?php

namespace App\Portman\Configuration\Data;

use App\Portman\Configuration\ConfigurationException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Webmozart\Glob\Glob;

class Directory extends Data
{

    protected array|null $paths = null;

    /**
     * @param string            $path
     * @param Optional|string   $glob
     * @param Optional|string[] $ignore
     */
    public function __construct(
        public string          $path,
        public Optional|string $glob = '**/*.php',
        public Optional|array  $ignore = [],
    )
    {
    }

    public function getAllFilePaths(): array
    {
        if ($this->paths) {
            return $this->paths;
        }
        // check options and set defaults if not set
        $directory = trim(trim($this->path), DIRECTORY_SEPARATOR);
        $glob      = DIRECTORY_SEPARATOR . trim(trim($this->glob), DIRECTORY_SEPARATOR);
        if (!realpath($directory)) {
            throw new ConfigurationException('The directory is not found: ' . $directory);
        }

        // Collect all paths in directory
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $paths    = [];
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $file = substr($path, strlen($directory));
            if ($item->isFile() && Glob::match($file, $glob)) {
                $file         = substr($path, strlen($directory));
                $paths[$file] = $path;
            }
        }

        // process the ignore option to collect paths to ignore
        $files       = array_keys($paths);
        $ignoreFiles = [];
        foreach ($this->ignore as $ignoreGlob) {
            // check if this ignore should negate filtered keys.
            $negate = str_starts_with($ignoreGlob, '!');
            // sanitize the glob to process the files
            $glob      = DIRECTORY_SEPARATOR . trim(trim($ignoreGlob), '!' . DIRECTORY_SEPARATOR);
            $globFiles = Glob::filter($files, $glob);
            if ($negate) {
                // remove the found files from the already ignored files
                $ignoreFiles = array_diff($ignoreFiles, $globFiles);
            }
            else {
                // add the found files to the ignore list
                $ignoreFiles = [
                    ...$ignoreFiles,
                    ...$globFiles
                ];
            }
        }

        $this->paths = array_diff_key($paths, array_fill_keys($ignoreFiles, null));

        return $this->paths;
    }
}