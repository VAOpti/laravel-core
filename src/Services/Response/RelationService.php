<?php

namespace VisionAura\LaravelCore\Services\Response;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class RelationService
{
    use HasErrorBag;

    public bool $hasRelations = false;

    public array $relations = [];

    protected Model $model;

    /**
     * @param  Model  $model  The model to retrieve relations from
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        $includes = request()->query->getString('include');
        $this->relations = explode(',', $includes);

        $this->hasRelations = ((bool) $this->relations[ 0 ]);

        $this->validateRelations();
    }

    protected function validateRelations(): true
    {
        if (! $this->hasRelations) {
            return true;
        }

        foreach ($this->relations as $relation) {
            if (! $this->model->isRelation($relation)) {
                $this->getErrors()->push(
                    __('Could not find the specified relation.'),
                    sprintf("The relation '$relation' does not exist on '%s'", get_class($this->model)),
                    'include='.request()->query->getString('include'),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $this->checkErrors();

        return true;
    }
}