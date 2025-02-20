<?php

namespace App\Portman\Configuration\Data\Mappers;

use Illuminate\Support\Str;
use Spatie\LaravelData\Mappers\NameMapper;

class KebabCaseMapper implements NameMapper
{
    public function map(int|string $name): string|int
    {
        return Str::kebab($name);
    }
}
