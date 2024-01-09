<?php

namespace VisionAura\LaravelCore\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Rules\ExistsRelationId;
use VisionAura\LaravelCore\Rules\ValidRelation;

class CoreRequest extends FormRequest
{
    /** @var array<string, string[]> $rules Used to store the used rules into. */
    private array $rules = [];

    /** @var array<string, string[]> $writeRules */
    private array $writeRules = [
        'data'            => ['required', 'array'],
        'data.type'       => ['required', 'string'],
        'data.attributes' => ['required', 'array'],
    ];

    private array $relationshipRules = [
        'data.relationships'        => ['required', 'array'],
        'data.relationships.*.data' => ['required', 'array'],
        'data.relationships.*.type' => ['required', 'string'],
        'data.relationships.*.id'   => ['required', 'string'],
    ];

    /** @inheritdoc */
    public function validated($key = null, $default = null): mixed
    {
        $key = $key ? "data.attributes.{$key}" : 'data.attributes';

        return data_get($this->getValidatorInstance()->validated(), $key, $default);
    }

    public function prepareForValidation(): void
    {
        if (request()->isMethod(self::METHOD_POST)) {
            $storeRules = Arr::prependKeysWith($this->storeRules(), 'data.attributes.');
            $this->rules = [...$this->writeRules, ...$storeRules];

            return;
        }

        if (request()->isMethod(self::METHOD_PATCH) || request()->isMethod(self::METHOD_PUT)) {
            $this->rules[ 'data.id' ] = ['required', 'string'];

            if (str_contains(request()->path(), 'relationships')) {
                // TODO: updating relations does not work yet.
                unset($this->rules[ 'data.attributes' ]);
                $this->rules = [...$this->writeRules, ...$this->relationshipRules];
                $updateRules = Arr::prependKeysWith($this->updateRules(), 'data.relationships.');
                $this->rules = $this->updateRelationRules();

                return;
            }

            $updateRules = Arr::prependKeysWith($this->updateRules(), 'data.attributes.');
            $this->rules = [...$this->writeRules, ...$updateRules];

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
            'type' => ['required', 'string', new ValidRelation],
            'id'   => ['required', 'string', new ExistsRelationId],
        ];

        $prepend = Str::of('data.relationships.*.data.');
        if ($this->request->has('data.relationships')) {
            $payload = $this->request->all('data');
            if (count($payload) !== count($payload, COUNT_RECURSIVE)) {
                // data is a multidimensional array
                $prepend->append('*.');
            }
        }

        $rules = Arr::prependKeysWith($rules, $prepend);

        return [...['data' => ['required', 'array'], ...$rules]];
    }
}
