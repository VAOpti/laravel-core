<?php

use Illuminate\Database\Eloquent\Model;

if (! function_exists('pluralizeModel')) {
    function pluralizeModel(\VisionAura\LaravelCore\Interfaces\RelationInterface|Model $model): string {
        return (string) Illuminate\Support\Str::of(class_basename($model))->plural()->lower();
    }
}

if (! function_exists('flattenRelations')) {
    function flattenRelations(Model $model): Model
    {
        /** @var \Illuminate\Support\Collection[] $relationCollection */
        $relationCollection = $model->getRelations();

        if (! $relationCollection) {
            return $model;
        }

        foreach ($relationCollection as $name => $relations) {
            /** @var Model|bool $relation */
            foreach ($relations as $relation) {
                if (! ($relation instanceof Model)) {
                    continue;
                }

                /** @var \Illuminate\Support\Collection[] $subRelationCollection */
                $subRelationCollection = $relation->getRelations();

                foreach ($subRelationCollection as $subName => $subRelations) {
                    if (! $subRelations) {
                        continue;
                    }

                    $subRelations->each(function (Model $model) {
                        return flattenRelations($model);
                    });

                    $model->setRelation("{$name}.{$subName}", $subRelations);
                    $relation->unsetRelation($subName);
                }
            }
        }

        return $model;
    }
}
