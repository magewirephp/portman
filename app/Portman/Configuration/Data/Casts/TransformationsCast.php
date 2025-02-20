<?php

namespace App\Portman\Configuration\Data\Casts;

use App\Portman\Configuration\Data\SourceDirectory;
use App\Portman\Configuration\Data\Transformation;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class TransformationsCast implements Cast
{

    /**
     * @param DataProperty    $property
     * @param mixed           $value
     * @param array           $properties
     * @param CreationContext $context
     *
     * @return Transformation[]|Uncastable
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): array|Uncastable
    {
        if (is_array($value)) {
            $array = [];
            $level = 0;
            if (isset($properties['level']) && is_int($properties['level'])) {
                $level = $properties['level'] + 1;
            }
            $sort = 0;
            foreach ($value as $name => $val) {
                if (is_string($name) && is_array($val)) {
                    if (isset($properties['name']) && is_string($properties['name'])) {
                        $name = trim($properties['name'], '\\')  . '\\' . $name;
                    }
                    $rename = null;
                    if (isset($val['rename']) && is_string($val['rename'])) {
                        $rename = $val['rename'];
                        unset($val['rename']);
                        if (isset($properties['rename']) && is_string($properties['rename'])) {
                            $rename = (($properties['rename'] ? trim($properties['rename'], '\\')  . '\\' : '') . $rename);
                        }
                    }
                    $array[] = Transformation::from(['name' => $name, 'rename' => $rename, 'level' => $level, 'sort' => $sort, ...$val]);
                    $sort++;
                }
                else {
                    throw new \Exception('Invalid transformation: ' . json_encode($value));
                }
            }

            return count($array) > 0 ? $array : Uncastable::create();
        }

        return Uncastable::create();
    }
}