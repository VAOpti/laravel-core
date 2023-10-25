<?php

namespace VisionAura\LaravelCore\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class CoreRepository
{
    use HasErrorBag;

    protected function simpleStore(Model $model, array $attributes): Model
    {
        $model = new $model();
        $model->fill($attributes);
        $model->save();

        return $model->fresh();
    }

    protected function simpleUpdate(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }
}