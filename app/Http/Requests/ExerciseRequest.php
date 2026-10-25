<?php

namespace App\Http\Requests;

use App\Models\Exercise;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExerciseRequest extends FormRequest
{
    /**
     * Tidy whitespace so "  Bench   press " and "Bench press" are treated as the same name.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
            'category' => preg_replace('/\s+/', ' ', trim((string) $this->input('category'))) ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueForTrainer(...)],
            'category' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Reject a name the trainer already uses for another exercise, ignoring case.
     */
    private function uniqueForTrainer(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Exercise|null $current */
        $current = $this->route('exercise');

        $taken = $this->user()->exercises()
            ->whereRaw('lower(name) = ?', [mb_strtolower((string) $value)])
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->exists();

        if ($taken) {
            $fail('You already have an exercise with this name.');
        }
    }
}
