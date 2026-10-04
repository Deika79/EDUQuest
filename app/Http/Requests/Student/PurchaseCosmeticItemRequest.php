<?php

namespace App\Http\Requests\Student;

use App\Models\CosmeticItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseCosmeticItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('cosmeticItem');

        return $item instanceof CosmeticItem && $this->user()->can('purchase', $item);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'student_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'coin_price' => ['prohibited'],
            'minimum_level' => ['prohibited'],
            'character_key' => ['prohibited'],
            'amount' => ['prohibited'],
        ];
    }
}
