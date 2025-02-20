<?php

use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use App\Providers\AppServiceProvider;

return [
    TranslationServiceProvider::class,
    ValidationServiceProvider::class,
    LaravelDataServiceProvider::class,
    AppServiceProvider::class,
];
