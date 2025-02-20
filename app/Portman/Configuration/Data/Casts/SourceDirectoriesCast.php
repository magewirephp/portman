<?php

namespace App\Portman\Configuration\Data\Casts;

use App\Portman\Configuration\Data\SourceDirectory;
use App\Portman\Configuration\Data\Transformation;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class SourceDirectoriesCast implements Cast
{


    /**
     * @param DataProperty    $property
     * @param mixed           $value
     * @param array           $properties
     * @param CreationContext $context
     *
     * @return SourceDirectory[]|Uncastable
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): array|Uncastable
    {
        if (is_array($value)) {
            if (count(array_intersect(['composer', 'glob', 'ignore'], array_keys($value))) > 0) {
                $array[] = SourceDirectory::from($value);
            }
            $array = [];
            foreach ($value as $key => $val) {
                if (is_string($key) && is_array($val)) {
                    $array[] = SourceDirectory::from(['path' => $key, ...$val]);
                }
                if (is_int($key) && is_string($val)) {
                    $array[] = SourceDirectory::from(['path' => $val]);
                }
            }

            return count($array) > 0 ? $array : Uncastable::create();
        }

        return Uncastable::create();
    }
}