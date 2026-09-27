<?php

namespace App\Rules;

use App\Enums\VideoProvider;
use App\Support\VideoReference;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidVideoReference implements ValidationRule
{
    public function __construct(private readonly ?VideoProvider $provider) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || VideoReference::extractId($this->provider, $value) === null) {
            $fail('Use a valid video URL or identifier for the selected provider.');
        }
    }
}
