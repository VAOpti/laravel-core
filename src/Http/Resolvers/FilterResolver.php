<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Casts\CastPrimitives;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Exceptions\InvalidRelationException;
use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;
use VisionAura\LaravelCore\Interfaces\RelationInterface;

class FilterResolver
{
    protected Model&RelationInterface $model;

    /** @var array{}|array<array{int, relation:string|null, attribute:string, operator:FilterOperatorsEnum, value: mixed}> */
    protected array $query = [];

    protected bool $hasFilter = false;

    public function __construct(Model&RelationInterface $model)
    {
        $this->model = $model;

        $filters = request()->all('filter');

        if (! Arr::get($filters, 'filter')) {
            return;
        }

        $filters = Arr::get($filters, 'filter');

        Arr::map($filters, function ($val, $key) {
            [$attribute, $relation] = $this->resolveAttributeAndRelation($key);
            $operator = FilterOperatorsEnum::EQUALS;

            if (is_array($val)) { // If $val is an array, it means an operator is specified.
                $queryOperator = array_keys($val)[ 0 ];
                $operator = $this->resolveOperator($queryOperator, $relation);
                if (! $operator) {
                    throw new CoreException(ErrorBag::make(
                        __('core::errors.Invalid filter operator'),
                        'An invalid operator \''.array_keys($val)[ 0 ].'\' was used on a filter in the query.',
                        'filter['.($relation ? "$relation." : '')."$attribute][".array_keys($val)[ 0 ]."]={$val[ $queryOperator ]}",
                        Response::HTTP_BAD_REQUEST
                    )->bag);
                }

                $val = $val[ $queryOperator ];
            }

            $value = $this->resolveValue($val, $attribute, $relation);

            $this->add($operator, $value, $attribute, $relation);
        });
    }

    public function add(string|FilterOperatorsEnum $operator, mixed $value, ?string $attribute = null, ?string $relation = null): self
    {
        $this->query[] = [
            'relation'  => $relation,
            'attribute' => $attribute,
            'operator'  => $operator,
            'value'     => $value,
        ];

        return $this;
    }

    /**
     * Check if the attribute is an attribute on the model. If not, check if it's a valid relation
     *
     * @param  string  $key
     *
     * @return array{}|array{attribute?:string, relation?:string}|null
     * @throws InvalidRelationException
     */
    public function resolveAttributeAndRelation(string $key): ?array
    {
        $attribute = function (RelationInterface&Model $owner, string $name): ?string {
            if (AttributeResolver::verify($owner, $name)) {
                return $name;
            }

            return null;
        };

        $possibleAttribute = $key;
        $owner = $this->model;

        if ($this->model->verifyRelation($key)) {
            // Filter queries on a relation, not an attribute.
            return [null, $key];
        }

        if (str_contains($key, '.')) {
            // Try to resolve the attribute by taking the last attribute in the path.
            [$relation, $possibleAttribute] = split_on_last($key);
            $owner = $this->model->getRelated($relation);

            if (! $owner) {
                throw new InvalidRelationException("A non-existing relationship was requested: $relation", "filter[{$key}]");
            }
        }

        return [$attribute($owner, $possibleAttribute), $relation ?? null];
    }

    public function resolveOperator(string $operator, ?string $relation = null): string|FilterOperatorsEnum|null
    {
        $resolved = FilterOperatorsEnum::tryFrom($operator);
        if ($resolved) {
            return $resolved;
        }

        $owner = $relation ? ($this->model->getRelated($relation) ?? $this->model) : $this->model;

        // If the operator is not in the Enum, check if it's a custom (scope) filter method.
        return method_exists($owner, "scope{$operator}") ? $operator : null;
    }

    public function resolveValue(mixed $value, ?string $attribute = null, ?string $relation = null): mixed
    {
        if ($value === null) {
            // The value was not set in the query parameter and therefore null
            return null;
        }

        $casts = function () use ($relation) {
            if (! $relation) {
                return $this->model->getCasts();
            }

            return $this->model->getRelated($relation)?->getCasts() ?? [];
        };

        if ($attribute === null && $relation) {
            // The value is probably a boolean to query whether an relation exists or not.
            return (new CastPrimitives($value))->castBool();
        }

        return (new CastPrimitives($value, $casts()[ $attribute ] ?? ''))->cast();
    }
}