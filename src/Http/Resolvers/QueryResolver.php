<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;

final class QueryResolver
{
    protected Collection|LengthAwarePaginator $resolved;

    protected Model $model;

    protected AttributeResolver $attributes;

    protected RelationResolver $includes;

    protected PaginateResolver $pagination;

    /**
     * @throws CoreException
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        // Important: run the RelationService first to validate all relationships.
        // They can then be assumed to be safe when querying for attributes.
        $this->includes = new RelationResolver($model);
        $this->pagination = new PaginateResolver($model);
        $this->attributes = new AttributeResolver();
    }

    /** @return string[] */
    public function attributes(?string $of = null): array
    {
        $name = pluralizeModel($this->model);

        // Check if necessary foreign keys are filtered out - force include and hide them if so.
        foreach (array_extrude($this->includes->relations) as $parent => $relation) {
            if (is_int($parent)) { // The include does not have a child specified
                $this->resolveForeignKey($this->model, $relation, $name);

                continue;
            }

            // The include has a child specified.
            $parentModel = $this->model;

            $ritit = new RecursiveIteratorIterator(new RecursiveArrayIterator([$parent => $relation]));
            foreach ($ritit as $leaf) {
                foreach (range(0, $ritit->getDepth()) as $depth) {
                    $parent = $ritit->getSubIterator($depth)->key();
                    // Does not seem necessary
                    // $relation = $ritit->getSubIterator($depth)->current();
                    // $relation = is_array($relation) ? array_keys($relation)[0] : $relation;

                    $this->resolveForeignKey($parentModel, $parent, pluralizeModel($parentModel));
                    $parentModel = $parentModel->{$parent}()->getRelated();
                }
            }

        }

        return $this->attributes->get($this->model, $of ?? $name, Arr::get($this->includes->force, $of ?? $name, []));
    }

    public function resolveRelations(): self
    {
        if (! $this->includes->hasRelations) {
            return $this;
        }

        $this->resolved->each(function (Model $model) {
            $model = flattenRelations($model);

            $hiddenAttrs = array_intersect(array_keys($model->getRelations()), array_keys($this->includes->force));
            foreach ($hiddenAttrs as $hiddenAttr) {
                /** @var Collection $relationCollection */
                $relationCollection = $model->getRelation($hiddenAttr);
                $relationCollection->each(function (Model $model) use ($hiddenAttr){
                    return $model->setHidden(Arr::get($this->includes->force, $hiddenAttr));
                });
            }

            $stepParents = array_diff(array_keys($model->getRelations()), $this->includes->relations);
            foreach ($stepParents as $stepParent) {
                $model->unsetRelation($stepParent);
            }

            return $model;
        });

        return $this;
    }

    /**
     * @throws CoreException
     */
    public function resolve(Builder $query): Collection|LengthAwarePaginator
    {
        $this->resolveQuery($query)->resolveRelations();

        // Hide attributes that were added for the purpose of loading the relationship
        $name = pluralizeModel($this->model);
        if (Arr::has($this->includes->force, $name)) {
            $this->resolved->transform(function (Model $model) use ($name) {
                return $model->setHidden(Arr::get($this->includes->force, $name));
            });
        }

        return $this->resolved;
    }


    /**
     * @throws CoreException
     */
    protected function resolveQuery(Builder $query): self
    {
        $with = [];
        foreach ($this->includes->relations as $include) {
            if (str_contains($include, '.')) { // skip for now
                continue;
            }

            $this->resolveForeignKey($this->model, $include);

            $includeAttr = $this->attributes->get($this->model, $include, Arr::get($this->includes->force, $include));
            if ($includeAttr[ 0 ] === '*') {
                $with[] = $include;

                continue;
            }

            $with[] = "$include:".implode(',', $includeAttr);
        }

        $query->with($with);

        try {
            if ($this->pagination->hasPagination) {
                $this->resolved = $query->paginate(perPage: $this->pagination->getPerPage(),
                    page: $this->pagination->getPage());
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

    /**
     * @param  Model        $from          The model to base the relation from
     * @param  string       $relation      The name of the relation
     * @param  string|null  $attributeKey  The name of the key this should be forced on to.
     *
     * @return self
     */
    protected function resolveForeignKey(Model $from, string $relation, ?string $attributeKey = null): self
    {
        if (! $attributeKey) {
            $attributeKey = $relation;
        }

        if (method_exists($from->$relation(), 'getForeignKeyName')
            && ! in_array($from->$relation()->getForeignKeyName(), $this->attributes->getVisibleAttributes($attributeKey))
        ) {
            // If the relation is not the same as the attribute key, it's assumed the foreign key is on the parent,
            // in which case it should be checked if it does indeed.
            if ($relation !== $attributeKey
                && ! in_array($from->$relation()->getForeignKeyName(), Schema::getColumnListing($this->model->getTable()))
            ) {
                return $this;
            }

            $foreignKey = $from->$relation()->getForeignKeyName();

            $this->includes->force[ $attributeKey ][] = $foreignKey;
        }

        return $this;
    }
}
