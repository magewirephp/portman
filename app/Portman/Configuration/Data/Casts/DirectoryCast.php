<?php

namespace App\Portman\Configuration\Data\Casts;

use App\Portman\Configuration\Data\Directory;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class DirectoryCast implements Cast
{

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Directory|Uncastable
    {
        if (is_string($value)) {
            return Directory::from(['path' => $value]);
        }

        return Uncastable::create();
    }
}