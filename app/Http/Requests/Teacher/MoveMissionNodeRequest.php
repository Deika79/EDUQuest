<?php

namespace App\Http\Requests\Teacher;

use App\Models\Mission;
use App\Models\MissionNode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveMissionNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');
        $node = $this->route('node');

        return $mission instanceof Mission
            && $node instanceof MissionNode
            && $node->mission_id === $mission->id
            && $this->user()->can('update', $mission);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['direction' => ['required', Rule::in(['up', 'down'])]];
    }
}
