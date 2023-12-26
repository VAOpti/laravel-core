<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;
use VisionAura\LaravelCore\Http\Enums\QueryTypeEnum;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;
use VisionAura\LaravelCore\Support\Facades\RequestFilter;

class ApiResponseResolver
{
    protected QueryResolver $queryResolver;

    protected Model $model;

    protected string $requestId;

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

    public function resource(): GenericResource
    {
        if (isset($this->requestId)) {
            $resource = $this->resolveResourceFromId($this->requestId);
        } elseif ((RequestFilter::hasFilter() || $this->queryResolver->attributes->hasHiddenAttributes)) {
            if (! $this->model->getAttribute($this->model->getKeyName())) {
                throw new CoreException(
                    ErrorBag::make(__('core::errors.Server error'),
                        'The model passed for resolving did not contain the primary key.'
                    )->bag
                );
            };

            $resource = $this->resolveResourceFromId($this->model->getAttribute($this->model->getKeyName()));
        }

        // If there is no filter or fields attribute is set, we don't have to pass it through the solver;
        // TODO: Actually we should, sorting also needs to be solved.
        return new GenericResource($resource ?? $this->model);
    }

    public function from(string $id): self
    {
        $this->requestId = $id;

        return $this;
    }

    protected function resolveResourceFromId(string $id): Model
    {
        RequestFilter::addClause(value: $id, type: QueryTypeEnum::WHERE, attribute: $this->model->getKeyName(), operator: FilterOperatorsEnum::EQUALS);
        $resourceQuery = $this->model::select($this->queryResolver->attributes());

        return $this->queryResolver->resolve($resourceQuery, true);
    }
}
