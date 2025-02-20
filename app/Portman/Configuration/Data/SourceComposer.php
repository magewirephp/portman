<?php

namespace App\Portman\Configuration\Data;

use App\Portman\Configuration\Data\Validation\VersionConstraintValidator;
use App\Portman\Configuration\Data\Validation\VersionValidator;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class SourceComposer extends Data
{
    public function __construct(
        #[Regex('/^[a-z0-9]([_.-]?[a-z0-9]++)*+\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]++)*+$/')]
        public string $name,
        #[Rule(new VersionConstraintValidator())]
        public string $version,
        #[Rule(new VersionValidator())]
        public Optional|string $versionLock,
        public Optional|string $basePath,
    )
    {
    }


    public static function messages(): array
    {
        return [
            'name.regex' => 'Invalid composer package name.',
            'versionLock.regex' => 'Invalid version string.',
        ];
    }
}