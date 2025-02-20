<?php

namespace App\Portman\Configuration\Data\Validation;

use Closure;
use Composer\Package\Version\VersionParser;
use Illuminate\Contracts\Validation\ValidationRule;

class VersionConstraintValidator implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            (new VersionParser())->parseConstraints($value);
        }
        catch (\UnexpectedValueException $e) {
            $fail($e->getMessage());
        }
    }
}