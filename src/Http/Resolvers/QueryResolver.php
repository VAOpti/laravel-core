<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;

final class QueryResolver
{
    protected Model $model;

    public AttributeResolver $attributes;

    public RelationResolver $includes;

    public PaginateResolver $pagination;

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
    public function attributes(): array
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

            $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator([$parent => $relation]));
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

    public function relations(Collection|LengthAwarePaginator $collection): Collection|LengthAwarePaginator
    {
        if (! $this->includes->hasRelations) {
            return $collection;
        }

        return $collection->load($this->includes->relations);
    }

    public function resolve(Builder $query): Collection|LengthAwarePaginator
    {
        try {
            if ($this->pagination->hasPagination) {
                $collection = $query->paginate(perPage: $this->pagination->getPerPage(),
                    page: $this->pagination->getPage());
            } else {
                $collection = $query->get();
            }
        } catch (QueryException $exception) {
            throw new CoreException(ErrorBag::make(
                __('core::errors.Server error'),
                'An unknown field was requested.',
                ErrorBag::paramsFromQuery('fields'),
                Response::HTTP_INTERNAL_SERVER_ERROR)->bag
            );
        }

        $collection = $this->relations($collection);

        // Hide attributes that were added for the purpose of loading the relationship
        $name = pluralizeModel($this->model);
        if (Arr::has($this->includes->force, $name)) {
            $collection = $collection->transform(function (Model $model) use ($name) {
                return $model->setHidden(Arr::get($this->includes->force, $name));
            });
        }

        $collection->each(function (Model $model) {
            $model = flattenRelations($model);
            $stepParents = array_diff(array_keys($model->getRelations()), $this->includes->relations);

            foreach ($stepParents as $stepParent) {
                $model->unsetRelation($stepParent);
            }

            return $model;
        });

        return $collection;
    }

    protected function resolveForeignKey(Model $from, string $relation, string $attributeKey): self
    {
        if (method_exists($from->$relation(), 'getForeignKeyName')
            && ! in_array($from->$relation()->getForeignKeyName(),
                $this->attributes->getVisibleAttributes($attributeKey))
            && in_array($from->$relation()->getForeignKeyName(), Schema::getColumnListing($this->model->getTable()))
        ) {
            $foreignKey = $from->$relation()->getForeignKeyName();

            $this->includes->force[ $attributeKey ][] = $foreignKey;
        }

        return $this;
    }
}
