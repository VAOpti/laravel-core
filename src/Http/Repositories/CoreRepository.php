<?php

namespace VisionAura\LaravelCore\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Support\Facades\RequestController;
use VisionAura\LaravelCore\Traits\ApiResponse;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class CoreRepository
{
    use AuthorizesRequests, HasErrorBag, ApiResponse;

    protected Model $model;

    public function __construct(Model&RelationInterface $model)
    {
        $this->model = $model;
    }

    /**
     * Handle calls to missing methods on the repository.
     *
     * @param  string        $method
     * @param  array<mixed>  $parameters
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters)
    {
        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    public function getModel(): Model&RelationInterface
    {
        return $this->model;
    }

    public function updateRelation(CoreRequest $request, string $id, string $relation)
    {
        $this->authorize('replace', [$this->model, $relation]);

        $relation = Str::studly($relation);
        $method = "update{$relation}";

        $this->{$method}(RequestController::getRequest());
    }

    protected function simpleStore(Model $model, array $attributes): ?Model
    {
        $model = new $model();
        $model->fill($attributes);
        $model->save();
        $this->model = $model->fresh();

        return $this->model;
    }

    protected function insert(): Model
    {
        $this->model->save();
        $this->model = $this->model->fresh();

        return $this->model;
    }

    /** @param  array<mixed>  $attributes */
    protected function make(Model $model, array $attributes): self
    {
        $this->model = new $model();
        $this->model->fill($attributes);

        return $this;
    }

    protected function setAttribute(string $column, mixed $attribute): self
    {
        $this->model->{$column} = $attribute;

        return $this;
    }

    protected function setCreator(string $column = 'created_by_user_id'): self
    {
        $this->setAttribute($column, request()->user()->id);

        return $this;
    }

    protected function simpleUpdate(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();
        $this->model = $model;

        return $this->model;
    }
}
