<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Nette\NotImplementedException;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Casts\CastPrimitives;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Exceptions\InvalidRelationException;
use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Structs\FilterClauseStruct;

class FilterResolver
{
    protected Model&RelationInterface $model;

    /** @var FilterClauseStruct[] */
    protected array $clauses = [];

    protected bool $hasFilter = false;

    public function __construct(Model&RelationInterface $model)
    {
        // TODO: Make it possible to have the value be an array, so the filter can deduct it's a WHEREIN clause
        $this->model = $model;

        $filters = request()->all('filter');

        if (! Arr::get($filters, 'filter')) {
            return;
        }

        $this->hasFilter = true;

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
        $this->clauses[] = new FilterClauseStruct($relation, $attribute, $operator, $value);

        return $this;
    }

    /** @return array{}|FilterClauseStruct[] */
    public function get(): array
    {
        return $this->clauses;
    }

    /**
     * @deprecated Only get() seems necessary. All filters are done on the main resource level, not relation level.
     *
     * @return array{}|FilterClauseStruct[]
     */
    public function getMain(): array
    {
        return Arr::where($this->clauses, function (FilterClauseStruct $args) {
            return ($args->relation === null) || ($args->attribute === null && $args->relation !== null);
        });
    }

    /**
     * @deprecated Only get() seems necessary. All filters are done on the main resource level, not relation level.
     *
     * @return array{}|FilterClauseStruct[]
     */
    public function getRelations(?string $relation = null): array
    {
        return Arr::where($this->clauses, function (FilterClauseStruct $args) use ($relation) {
            return $relation
                ? ($args->relation === $relation && $args->attribute !== null)
                : ($args->relation !== null && $args->attribute !== null);
        });
    }

    /** @param  array{}|FilterClauseStruct[]  $clauses */
    public function bind(Builder $query, array $clauses): Builder
    {
        // TODO: Find a way to specify ORs in the query.
        foreach ($clauses as $clause) {
            if ($clause->relation !== null && $clause->attribute === null) {
                $where = $clause->value ? 'whereHas' : 'doesntHave';
                $query->{$where}($clause->relation);

                continue;
            }

            if (is_string($clause->operator)) {
                // Operator is a scope function
                if ($clause->relation) {
                    throw new NotImplementedException('Can\'t use scope functions on relations yet.', Response::HTTP_NOT_IMPLEMENTED);
                }

                $query->{$clause->operator}();

                continue;
            }

            if (! $clause->relation) {
                $query->where($clause->attribute, $clause->operator->toOperator(), $clause->resolveValue());

                continue;
            }

            $query->whereHas($clause->relation, function (Builder $query) use ($clause) {
                $query->where($clause->attribute, $clause->operator->toOperator(), $clause->resolveValue());
            });
        }

        return $query;
    }

    /**
     * Check if the attribute is an attribute on the model. If not, check if it's a valid relation
     *
     * @param  string  $key
     *
     * @return array{}|array{attribute?:string, relation?:string}|null
     * @throws InvalidRelationException
     */
    private function resolveAttributeAndRelation(string $key): ?array
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

    private function resolveOperator(string $operator, ?string $relation = null): string|FilterOperatorsEnum|null
    {
        $resolved = FilterOperatorsEnum::tryFrom($operator);
        if ($resolved) {
            return $resolved;
        }

        $owner = $relation ? ($this->model->getRelated($relation) ?? $this->model) : $this->model;

        // If the operator is not in the Enum, check if it's a custom (scope) filter method.
        return method_exists($owner, "scope{$operator}") ? $operator : null;
    }

    private function resolveValue(mixed $value, ?string $attribute = null, ?string $relation = null): mixed
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
            // The value is probably a boolean to query whether a relation exists or not.
            return (new CastPrimitives($value))->castBool();
        }

        return (new CastPrimitives($value, $casts()[ $attribute ] ?? ''))->cast();
    }
}