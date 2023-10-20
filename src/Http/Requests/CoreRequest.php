<?php

namespace VisionAura\LaravelCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoreRequest extends FormRequest
{
    /** @var array<string, string[]> $rules */
    private array $rules = [];

    public function prepareForValidation(): void
    {
        if (request()->isMethod(self::METHOD_POST)) {
            $this->rules = $this->storeRules();
        }

        if (request()->isMethod(self::METHOD_PATCH) || $this->isMethod(self::METHOD_PUT)) {
            $this->rules = $this->updateRules();
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Get the validation rules that apply to the store request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function storeRules(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the validation rules that apply to the update request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function updateRules(): array
    {
        return [
            //
        ];
    }
}
