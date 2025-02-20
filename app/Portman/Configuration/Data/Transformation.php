<?php

namespace App\Portman\Configuration\Data;

use App\Portman\Configuration\Data\Casts\TransformationsCast;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class Transformation extends Data
{
    #[Computed]
    public bool   $isClass;
    #[Computed]
    public array  $nameParts;
    #[Computed]
    public Optional|array $renameParts;

    /**
     * @param string                    $name
     * @param Optional|string|null      $rename
     * @param Optional|string           $fileDocBlock
     * @param Optional|string[]         $removeMethods
     * @param Optional|string[]         $removeProperties
     * @param Optional|Transformation[] $children
     * @param int                       $level
     * @param int                       $sort
     */
    public function __construct(
        public string               $name,
        public Optional|null|string $rename,
        public Optional|string      $fileDocBlock,
        public Optional|array       $removeMethods,
        public Optional|array       $removeProperties,
        #[WithCast(TransformationsCast::class)]
        public Optional|array       $children,
        public int                  $level = 0,
        public int                  $sort = 0,
    )
    {
        $this->isClass     = !str_ends_with($this->name, '\\');
        $this->name        = trim($this->name, '\\');
        $this->nameParts   = explode('\\', $this->name);
        $this->rename      = is_string($this->rename) ? trim($this->rename, '\\') : null;
        $this->renameParts = is_string($this->rename) ? explode('\\', $this->rename) : [];
    }
}