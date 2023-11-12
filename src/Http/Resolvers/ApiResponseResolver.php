<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;

class ApiResponseResolver
{
    protected Model $model;

    public QueryResolver $queryResolver;

    /**
     * @throws CoreException
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->queryResolver = new QueryResolver($model);
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
