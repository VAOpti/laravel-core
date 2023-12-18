<?php

namespace VisionAura\LaravelCore\Support\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Facade;
use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;
use VisionAura\LaravelCore\Http\Enums\QueryTypeEnum;
use VisionAura\LaravelCore\Http\Resolvers\FilterResolver;

/**
 * @method static array get()
 * @method static array getRelations(?string $relation = null)
 * @method static FilterResolver addClause(mixed $value, string|QueryTypeEnum $type = QueryTypeEnum::WHERE, ?string $attribute = null, FilterOperatorsEnum|null $operator = FilterOperatorsEnum::EQUALS, ?string $relation = null)
 * @method static boolean hasFilter()
 * @method static Builder|Relation bind(Builder|Relation $query, array $clauses) Takes a Builder or a Relation (usually from a whereHas() function) and adds the clauses to the query.
 */
class RequestFilter extends Facade
{
    /** @inheritdoc */
    protected static function getFacadeAccessor(): string
    {
        return 'filter';
    }
}
