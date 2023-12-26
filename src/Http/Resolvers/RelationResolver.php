<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Interfaces\RelationInterface;

class RelationResolver
{
    public bool $hasRelations = false;

    /** @var string[] $relations */
    public array $relations = [];

    /** @var RelationInterface $model */
    protected RelationInterface $model;

    /**
     * @param  RelationInterface  $model  The model to retrieve relations from
     *
     * @throws CoreException
     */
    public function __construct(RelationInterface $model, CoreRequest $request)
    {
        $typoIncludes = $request->query->getString('includes');

        if ($typoIncludes) {
            throw new CoreException(ErrorBag::make(
                title: 'Typo in filter parameter',
                description: 'An unknown parameter with the name \'includes\' was passed. Did you mean \'include\'?',
                status: Response::HTTP_BAD_REQUEST
            )->bag);
        }

        $this->model = $model;

        $includes = $request->query->getString('include');
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
