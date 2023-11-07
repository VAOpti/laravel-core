<?php

namespace VisionAura\LaravelCore\Services\Response;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;

class ApiResponseService
{
    protected Model $model;

    public QueryResolveService $queryResolver;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->queryResolver = new QueryResolveService($model);
    }

    public function collection(): GenericCollection
    {
        $collectionQuery = $this->model::select($this->queryResolver->attributes());
        $collection = $this->queryResolver->resolve($collectionQuery);

        return new GenericCollection($collection);
    }

    public function resource(): GenericResource
    {
        return new GenericResource($this->model);
    }
}