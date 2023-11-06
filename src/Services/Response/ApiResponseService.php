<?php

namespace VisionAura\LaravelCore\Services\Response;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;

final class ApiResponseService
{
    protected Model $model;

    protected AttributeService $attributes;

    protected RelationService $includes;

    protected PaginateService $pagination;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->attributes = new AttributeService();
        $this->includes = new RelationService($model);
        $this->pagination = new PaginateService($model);
    }

    public function collection(): GenericCollection
    {
        $name = (string) Str::of(class_basename($this->model))->plural()->lower();
        $attributes = $this->attributes->get($this->model, $name);

        $collectionQuery = $this->model::select($attributes);
        $collection = $this->pagination->handle($collectionQuery);

        if ($this->includes->hasRelations) {
            $collection->load($this->includes->relations);
        }

        return new GenericCollection($collection);
    }

    public function resource(): GenericResource
    {
        return new GenericResource($this->model);
    }
}