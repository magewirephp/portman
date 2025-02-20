<?php

namespace App\Portman\Configuration\Data\Casts;

use App\Portman\Configuration\Data\Directory;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class DirectoriesCast implements Cast
{

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): array|Optional|Uncastable
    {
        if (is_array($value)) {
            $array = [];
            foreach ($value as $key => $val) {
                if (is_int($key) && is_string($val)) {
                    $array[] = Directory::from(['path' => $val]);
                }
            }
            return count($array) > 0 ? $array : Optional::create();
        }

        return Uncastable::create();
    }
}