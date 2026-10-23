<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'performed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'entries' => ['required', 'array', 'min:1', 'max:50'],
            'entries.*.exercise' => ['required', 'string', 'max:100'],
            'entries.*.sets' => ['required', 'array', 'min:1', 'max:50'],
            'entries.*.sets.*.reps' => ['required', 'integer', 'between:1,1000'],
            'entries.*.sets.*.weight_kg' => ['nullable', 'numeric', 'between:0,1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'entries.*.exercise' => 'exercise',
            'entries.*.sets' => 'sets',
            'entries.*.sets.*.reps' => 'reps',
            'entries.*.sets.*.weight_kg' => 'weight',
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entries.required' => 'Add at least one exercise.',
            'entries.*.sets.required' => 'Add at least one set.',
        ];
    }
}
