<?php

namespace App\Http\Requests\Student;

use App\Models\AvatarProfile;
use App\Support\AvatarOptions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvatarProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AvatarProfile::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'character_key' => ['required', 'string', Rule::in(array_keys(AvatarOptions::CHARACTERS))],
            'student_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'setup_completed_at' => ['prohibited'],
            'skin_key' => ['prohibited'],
            'level' => ['prohibited'],
            'asset_path' => ['prohibited'],
            'image' => ['prohibited'],
            'html' => ['prohibited'],
        ];
    }
}
