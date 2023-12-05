<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;
use VisionAura\LaravelCore\Http\Enums\QueryTypeEnum;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;

class ApiResponseResolver
{
    protected Model $model;

    public QueryResolver $queryResolver;

    /**
     * @throws CoreException
     */
    public function __construct(Model $model, CoreRequest $request)
    {
        $this->model = $model;
        $this->queryResolver = new QueryResolver($model, $request);
    }

    public function collection(): GenericCollection
    {
        $collectionQuery = $this->model::select($this->queryResolver->attributes());
        $collection = $this->queryResolver->resolve($collectionQuery);

        return new GenericCollection($collection);
    }

    public function resource(string $id): GenericResource
    {
        $this->queryResolver->filter->addClause(QueryTypeEnum::WHERE, FilterOperatorsEnum::EQUALS, $id, $this->model->getKeyName());
        $resourceQuery = $this->model::select($this->queryResolver->attributes());
        $resource = $this->queryResolver->resolve($resourceQuery, true);

        return new GenericResource($resource);
    }
}
