<?php

namespace VisionAura\LaravelCore\Services\Response;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $name = (string) Str::of(class_basename($this->model))->plural()->lower();
        
        return $this->attributes->get($this->model, $name);
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
        if ($this->pagination->hasPagination) {
            $collection = $query->paginate(perPage: $this->pagination->getPerPage(), page: $this->pagination->getPage());
        } else {
            $collection = $query->get();
        }

        return $this->relations($collection);
    }
}
