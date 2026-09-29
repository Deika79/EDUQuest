<?php

namespace App\Http\Requests\Teacher;

use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateMissionDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Mission::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'request_token' => ['required', 'uuid'],
            'topic' => ['required', 'string', 'max:200'],
            'subject' => ['required', 'string', 'max:120'],
            'level' => ['required', 'string', 'max:80'],
            'objectives' => ['required', 'string', 'max:1500'],
            'difficulty' => ['required', Rule::in(['basic', 'intermediate', 'advanced'])],
            'node_count' => ['required', 'integer', 'between:4,8'],
            'instructions' => ['nullable', 'string', 'max:1500'],
        ];
    }

    /** @return array<string, mixed> */
    public function generationInput(): array
    {
        return $this->safe()->except('request_token');
    }
}
