<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Interfaces\RelationInterface;

final class QueryResolver
{
    protected Collection|LengthAwarePaginator $resolved;

    protected RelationInterface&Model $model;

    protected AttributeResolver $attributes;

    protected RelationResolver $includes;

    protected PaginateResolver $pagination;

    protected SortResolver $sort;

    protected FilterResolver $filter;

    /**
     * @throws CoreException
     */
    public function __construct(RelationInterface&Model $model)
    {
        $this->model = $model;

        // Important: run the RelationService first to validate all relationships.
        // They can then be assumed to be safe when querying for attributes.
        $this->includes = new RelationResolver($model);
        $this->pagination = new PaginateResolver($model);
        $this->attributes = new AttributeResolver();
        $this->sort = new SortResolver($model);
        $this->filter = new FilterResolver($model);

        $name = pluralizeModel($this->model);

        // Check if necessary foreign keys are filtered out.
        foreach (array_extrude($this->includes->relations) as $parent => $relation) {
            if (is_int($parent)) { // The include does not have a child specified.
                $this->checkDependency($this->model, $name, $relation);

                continue;
            }

            // The include has a child specified.
            $parentModel = $this->model;
            Arr::mapRecursive([$parent => $relation], function ($parent, $child) use (&$parentModel) {
                if (is_array($child)) {
                    $child = Arr::first(array_keys($child));
                }

                $this->checkDependency($parentModel, $parent, $child, "$parent.$child");

                $parentModel = $parentModel->{$child}()->getRelated();
            });
        }
    }

    /** @return string[] */
    public function attributes(?Model $model = null, ?string $of = null): array
    {
        return $this->attributes->getQualified($model ?? $this->model, $of ?? pluralizeModel($this->model));
    }

    /**
     * @throws CoreException
     */
    public function resolve(Builder $query): Collection|LengthAwarePaginator
    {
        $this->resolveQuery($query)->resolveRelations();

        // Hide attributes that were added for the purpose of loading the relationship
        $name = pluralizeModel($this->model);
        if ($this->attributes->getForced($name)) {
            $this->resolved->transform(function (Model $model) use ($name) {
                return $model->setHidden($this->attributes->getForced($name));
            });
        }

        return $this->resolved;
    }

    public function resolveRelations(): self
    {
        if (! $this->includes->hasRelations) {
            return $this;
        }

        $this->resolved->each(function (Model $model) {
            $model = flattenRelations($model);

            $hiddenAttrs = array_intersect(array_keys($model->getRelations()), array_keys($this->attributes->getForced()));
            foreach ($hiddenAttrs as $hiddenAttr) {
                /** @var Collection $relationCollection */
                $relationCollection = $model->getRelation($hiddenAttr);
                $relationCollection->each(function (Model $model) use ($hiddenAttr) {
                    return $model->setHidden($this->attributes->getForced($hiddenAttr));
                });
            }

            $stepParents = array_diff(array_keys($model->getRelations()), $this->includes->relations);
            foreach ($stepParents as $stepParent) {
                $model->unsetRelation($stepParent);
            }

            $missingRelations = array_diff($this->includes->relations, array_keys($model->getRelations()));
            foreach ($missingRelations as $missingRelation) {
                $model->setRelation($missingRelation, []);
            }

            return $model;
        });

        return $this;
    }

    /**
     * @throws CoreException
     */
    protected function resolveQuery(Builder $query): self
    {
        $query = $this->filter->bind($query, $this->filter->get());
        $query = $this->sort->bind($query);

        $with = [];
        foreach ($this->includes->relations as $include) {
            $callback = function (Relation $query) use ($include) {
                $selectedAttrs = function ($include): string {
                    $relevantTable = $include;

                    // Make sure we take the relevant table in a nested relation
                    if (str_contains($include, '.')) {
                        [$precedingPath, $relevantTable] = split_on_last($include);
                    }

                    $includeAttrs = $this->attributes->get($this->model, $include);

                    if (count($includeAttrs) === 1 && Arr::first($includeAttrs) === $include) {
                        return "$relevantTable.*";
                    }

                    $selectedAttrs = Arr::map($includeAttrs, function (string $attribute) use ($relevantTable) {
                        return "{$relevantTable}.{$attribute}";
                    });

                    return implode(",", $selectedAttrs);
                };

                $query->selectRaw($selectedAttrs($include));

                if ($clauses = $this->filter->getRelations($include)) {
                    $query = $this->filter->bind($query, $clauses);
                }

                return $query;
            };

            $with[ $include ] = $callback;
        }

        $query->with($with);

        try {
            if ($this->pagination->hasPagination) {
                $this->resolved = $query->paginate(perPage: $this->pagination->getPerPage(), page: $this->pagination->getPage());
            } else {
                $this->resolved = $query->get();
            }
        } catch (QueryException) {
            throw new CoreException(ErrorBag::make(
                __('core::errors.Server error'),
                'An unknown field was requested.',
                ErrorBag::paramsFromQuery('fields'),
                Response::HTTP_INTERNAL_SERVER_ERROR)->bag
            );
        }

        return $this;
    }

    protected function checkDependency(RelationInterface $model, string $parentName, string $relation, ?string $nestedKey = null): self
    {
        $dependentKeys = $model->resolveDependentKeys($relation);
        $foreignKeyOwner = $model->getForeignKeyOwner($relation);
        $this->attributes->forceDependentKeys($foreignKeyOwner, $model, $parentName, $relation, $dependentKeys, $nestedKey);

        return $this;
    }
}
