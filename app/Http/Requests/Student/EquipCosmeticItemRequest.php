<?php

namespace App\Http\Requests\Student;

use App\Models\StudentCosmeticItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EquipCosmeticItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ownership = $this->route('studentCosmeticItem');

        return $ownership instanceof StudentCosmeticItem && $this->user()->can('update', $ownership);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'student_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'cosmetic_item_id' => ['prohibited'],
            'character_key' => ['prohibited'],
        ];
    }
}
