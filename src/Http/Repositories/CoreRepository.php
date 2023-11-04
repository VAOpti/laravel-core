<?php

namespace VisionAura\LaravelCore\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class CoreRepository
{
    use HasErrorBag;

    protected Model $model;

    protected function simpleStore(Model $model, array $attributes): ?Model
    {
        $model = new $model();
        $model->fill($attributes);
        $model->save();

        return $model->fresh();
    }

    protected function insert(): ?Model
    {
        $this->model->save();

        return $this->model->fresh();
    }

    protected function make(Model $model, CoreRequest $request): self
    {
        $this->model = new $model();
        $this->model->fill($request->validated());

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

        return $model;
    }
}