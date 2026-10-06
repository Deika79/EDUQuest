<?php

namespace App\Http\Requests\Teacher;

use App\Enums\MissionMapTheme;
use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Mission::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'subject' => ['required', 'string', 'max:120'],
            'level' => ['required', 'string', 'max:80'],
            'map_theme' => ['sometimes', Rule::enum(MissionMapTheme::class)],
        ];
    }
}
