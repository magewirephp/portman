<?php

namespace App\Portman\Model;

readonly class VersionedFullyQualifiedName
{
    public FullyQualifiedName $original;
    public FullyQualifiedName $current;

    public function __construct()
    {
        $this->original = new FullyQualifiedName();
        $this->current  = new FullyQualifiedName();
    }
}