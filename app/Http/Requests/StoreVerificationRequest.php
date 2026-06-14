<?php

namespace App\Http\Requests;

use App\Consensus\Demo\ConsensusDemoFixtureCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ConsensusDemoFixtureCatalog $fixtures): array
    {
        return [
            'question' => ['required', 'string', 'min:8', 'max:2000'],
            'fixture_id' => ['required', 'string', Rule::in($fixtures->ids())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'question' => is_string($this->question) ? trim($this->question) : $this->question,
        ]);
    }
}
