<?php

namespace VisionAura\LaravelCore\Services\Response;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class RelationService
{
    use HasErrorBag;

    public bool $hasRelations = false;

    /** @var string[] $relations */
    public array $relations = [];

    /** @var string[] $force */
    public array $force = [];

    /** @var RelationInterface $model */
    protected RelationInterface $model;

    /**
     * @param  RelationInterface  $model  The model to retrieve relations from
     *
     * @throws CoreException
     */
    public function __construct(RelationInterface $model)
    {
        $this->model = $model;

        $includes = request()->query->getString('include');
        $this->relations = array_filter(explode(',', $includes));

        $this->hasRelations = ((bool) $this->relations);

        $this->validateRelations();
    }

    /**
     * @throws CoreException
     */
    protected function validateRelations(): true
    {
        if (! $this->hasRelations) {
            return true;
        }

        foreach ($this->relations as $relation) {
            $this->model->resolveRelation($relation);
        }

        return true;
    }
}