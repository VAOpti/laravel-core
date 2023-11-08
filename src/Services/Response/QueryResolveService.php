<?php

namespace VisionAura\LaravelCore\Services\Response;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;

final class QueryResolveService
{
    protected Model $model;

    public AttributeService $attributes;

    public RelationService $includes;

    public PaginateService $pagination;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->attributes = new AttributeService();
        $this->includes = new RelationService($model);
        $this->pagination = new PaginateService($model);
    }

    /** @return string[] */
    public function attributes(): array
    {
        $allAttributes = Schema::getColumnListing($this->model->getTable());
        $name = (string) Str::of(class_basename($this->model))->plural()->lower();

        // Check if necessary foreign keys are filtered out - force include and hide them if so.
        foreach ($this->includes->relations as $relation) {
            if (method_exists($this->model->$relation(), 'getForeignKeyName')
                && ! in_array($this->model->$relation()->getForeignKeyName(), $this->attributes->getVisibleAttributes($name))
                && in_array($this->model->$relation()->getForeignKeyName(), $allAttributes)
            ) {
                $foreignKey = $this->model->$relation()->getForeignKeyName();

                $this->includes->force[] = $foreignKey;
            }
        }

        return $this->attributes->get($this->model, $name, $this->includes->force);
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
        if ($this->includes->force) {
            $collection = $collection->transform(function (Model $model) {
                return $model->setHidden($this->includes->force);
            });
        }

        return $collection;
    }
}
