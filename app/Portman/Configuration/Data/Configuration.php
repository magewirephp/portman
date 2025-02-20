<?php

namespace App\Portman\Configuration\Data;

use App\Portman\Configuration\Data\Casts\TransformationsCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class Configuration extends Data
{
    /**
     * @param Directories    $directories
     * @param Optional|Transformation[] $transformations
     * @param Optional|array<string,bool> $postProcessors
     */
    public function __construct(
        public Directories          $directories,
        #[WithCast(TransformationsCast::class)]
        public Optional|array $transformations,
        public Optional|array $postProcessors,
    )
    {
    }
}