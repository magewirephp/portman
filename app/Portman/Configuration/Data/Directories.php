<?php

namespace App\Portman\Configuration\Data;

use App\Portman\Configuration\Data\Casts\DirectoriesCast;
use App\Portman\Configuration\Data\Casts\DirectoryCast;
use App\Portman\Configuration\Data\Casts\SourceDirectoriesCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class Directories extends Data
{
    /**
     * @param Directory                      $output
     * @param array<int, SourceDirectory>    $source
     * @param array<int, Directory>          $augmentation
     * @param Optional|array<int, Directory> $additional
     */
    public function __construct(
        #[WithCast(DirectoryCast::class)]
        public Directory      $output,
        #[WithCast(SourceDirectoriesCast::class)]
        public array          $source,
        #[WithCast(DirectoriesCast::class)]
        public array          $augmentation,
        #[WithCast(DirectoriesCast::class)]
        public Optional|array $additional = [],
    )
    {
    }

    public function findFilePathInAdditional(string $file): ?string
    {
        return self::findFilePathInDirectories($file, $this->additional);
    }

    /**
     * @param Directory[] $directories
     *
     * @return ?string
     */
    private static function findFilePathInDirectories(string $file, array $directories): ?string
    {
        foreach ($directories as $directory) {
            if (file_exists($directory->path . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR))) {
                return $directory->path . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR);
            }
        }

        return null;
    }

    public function getAdditionalPaths(): array
    {
        return array_map(fn(Directory $directory) => $directory->path, $this->additional);
    }

    public function getAllFilePathsOfAdditional(): array
    {
        return self::getAllFilePathsOfDirectories($this->additional);
    }

    /**
     * @param Directory[] $directories
     *
     * @return string[]
     */
    private static function getAllFilePathsOfDirectories(array $directories): array
    {
        $paths = [];
        foreach ($directories as $directory) {
            $paths = [...$paths, ...$directory->getAllFilePaths()];
        }

        return $paths;
    }

    public function findFilePathInAugmentation(string $file): ?string
    {
        return self::findFilePathInDirectories($file, $this->augmentation);
    }

    public function getAugmentationPaths(): array
    {
        return array_map(fn(Directory $directory) => $directory->path, $this->augmentation);
    }

    public function getAllFilePathsOfAugmentation(): array
    {
        return self::getAllFilePathsOfDirectories($this->augmentation);
    }

    public function findFilePathInSource(string $file): ?string
    {
        return self::findFilePathInDirectories($file, $this->source);
    }

    public function getSourcePaths(): array
    {
        return array_map(fn(Directory $directory) => $directory->path, $this->source);
    }

    public function getAllFilePathsOfSource(): array
    {
        return self::getAllFilePathsOfDirectories($this->source);
    }

    public function validateSourceDirectories(): string|true
    {

        $paths = [];
        foreach (portman_config_data()->directories->source as $directory) {
            if (!realpath($directory->path)) {
                if ($directory->composer instanceof SourceComposer) {
                    $paths[] = $directory->path;
                }
                else {
                    throw new \Exception("Source directory {$directory->path} does not exist");
                }
            }
        }

        return count($paths) > 0 ? join(', ', $paths) : true;
    }

}