<?php

namespace App\Portman\Configuration\Data\Validation;

use Closure;
use Composer\Package\Version\VersionParser;
use Illuminate\Contracts\Validation\ValidationRule;
use UnexpectedValueException;

class VersionValidator implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            (new VersionParser())->normalize($value);
        }
        catch (UnexpectedValueException|UnexpectedValueException $e) {
            $fail($e->getMessage());
        }
    }
}