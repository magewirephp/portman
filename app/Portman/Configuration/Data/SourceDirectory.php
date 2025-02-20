<?php

namespace App\Portman\Configuration\Data;

use Spatie\LaravelData\Optional;

class SourceDirectory extends Directory
{
    /**
     * @param string                  $path
     * @param Optional|SourceComposer $composer
     * @param Optional|string    $glob
     * @param Optional|string[]  $ignore
     */
    public function __construct(
        public string                  $path,
        public Optional|SourceComposer $composer,
        public Optional|string $glob='**/*.php',
        public Optional|array  $ignore=[],
    )
    {
        parent::__construct($path, $glob, $ignore);
    }
}