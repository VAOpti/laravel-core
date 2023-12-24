<?php

namespace VisionAura\LaravelCore\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use VisionAura\LaravelCore\Rules\ExistsRelationId;
use VisionAura\LaravelCore\Rules\ValidRelation;

class CoreRequest extends FormRequest
{
    /** @var array<string, string[]> $rules */
    private array $rules = [];

    public function prepareForValidation(): void
    {
        if (request()->isMethod(self::METHOD_POST)) {
            $this->rules = $this->storeRules();

            return;
        }

        if (request()->isMethod(self::METHOD_PATCH) || $this->isMethod(self::METHOD_PUT)) {
            if (str_contains(request()->path(), 'relationships')) {
                $this->rules = $this->updateRelationRules();

                return;
            }

            $this->rules = $this->updateRules();

            return;
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Get the validation rules that apply to the store request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function updateRules(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function updateRelationRules(): array
    {
        $rules = [
            'data.type' => ['required', 'string', new ValidRelation],
            'data.id'   => ['required', 'string'],
        ];

        if ($this->request->has('data')) {
            $root = $this->request->all('data');
            if (count($root) !== count($root, COUNT_RECURSIVE)) {
                // data is a multidimensional array, replace the given rules
                $rules = [
                    'data.*.type' => ['required', 'string', new ValidRelation],
                    'data.*.id'   => ['required', 'string', new ExistsRelationId],
                ];
            }
        }

        return [...['data' => ['required', 'array'], ...$rules]];
    }
}
