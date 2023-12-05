<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Structs\ParentChildRelationStruct;

class SortResolver
{
    /** @var string[] $sorts */
    protected array $sorts = [];

    protected RelationInterface $model;

    protected bool $hasSorts = false;

    public function __construct(RelationInterface $model, CoreRequest $request)
    {
        $sorts = explode(',', $request->query->getString('sort'));

        foreach (array_filter($sorts) as $sort) {
            $direction = 'asc';
            if (str_starts_with($sort, '-')) {
                $sort = ltrim($sort, '-');
                $direction = 'desc';
            }

            $this->sorts[ $sort ] = $direction;
        }

        $this->hasSorts = (bool) $this->sorts;

        $this->model = $model;
    }

    /** @return array{}|string[] */
    public function get(): array
    {
        return $this->sorts;
    }

    public function hasSorts(): bool
    {
        return $this->hasSorts;
    }

    public function bind(Builder $query): Builder
    {
        if (! $this->hasSorts) {
            return $query;
        }

        foreach ($this->sorts as $sort => $direction) {
            if (! str_contains($sort, '.')) {
                $query->orderBy($sort, $direction);

                continue;
            }

            $parts = explode('.', $sort);
            $sortColumn = last(array_splice($parts, -1));
            $sortColumn = last($parts).'.'.$sortColumn;

            $parentModel = $this->model;
            $relation = strtolower(class_basename($this->model)).'.'.implode('.', $parts);
            Arr::mapRecursive(array_extrude(Arr::wrap($relation)), function ($parent, $child) use (&$query, &$parentModel) {
                if (is_array($child)) {
                    $child = Arr::first(array_keys($child));
                }

                if (! $parentModel->resolveRelation($child)) {
                    return;
                }

                /** @var ParentChildRelationStruct $dependentKeys */
                $dependentKeys = $parentModel->resolveQualifiedDependentKeys($child);
                $query->leftJoin($child, $dependentKeys->owner, $dependentKeys->foreign);

                $parentModel = $parentModel->{$child}()->getRelated();
            });

            $query->orderBy($sortColumn, $direction);
        }

        return $query;
    }
}