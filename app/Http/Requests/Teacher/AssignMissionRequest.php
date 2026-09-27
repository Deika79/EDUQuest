<?php

namespace App\Http\Requests\Teacher;

use App\Models\Classroom;
use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof Mission && $this->user()->can('assign', $mission);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'classroom_ids' => ['required', 'array', 'min:1'],
            'classroom_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $input = $this->input('classroom_ids', []);

            if (! is_array($input)) {
                return;
            }

            $ids = collect($input)
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique();

            if ($ids->isEmpty() || $ids->count() !== count($input)) {
                return;
            }

            $ownedCount = Classroom::query()
                ->whereKey($ids)
                ->where('teacher_id', $this->user()->id)
                ->whereNull('archived_at')
                ->count();

            if ($ownedCount !== $ids->count()) {
                $validator->errors()->add(
                    'classroom_ids',
                    'Every selected class must be active and belong to your account.',
                );
            }
        }];
    }
}
