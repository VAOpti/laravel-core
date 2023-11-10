<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;

trait Relations
{
    /** @inheritdoc */
    public function resolveRelation(string $relation): string
    {
        assert($this instanceof Model, 'This trait should only be used on instances of '.Model::class);

        if (str_contains($relation, '.')) {
            $relations = explode('.', $relation);
            $parent = $relations[ 0 ];

            if ($this->isRelation($parent)) {
                $this->{$parent}()->getRelated()->resolveRelation(implode('.', array_splice($relations, 1)));
            }
        }

        if ($this->isRelation($relation)
            || (isset($parent) && $this->isRelation($parent))
        ) {
            return $relation;
        }

        $relation = Str::of($relation);
        $plural = (clone $relation)->plural()->value();
        $singular = $relation->singular()->value();

        foreach ([$plural, $singular] as $guess) {
            if ($this->isRelation($guess)) {
                $errorBag = ErrorBag::make(
                    __('core::errors.Could not find the requested resource.'),
                    "Did you mean the following relation: '$guess'?",
                    ErrorBag::paramsFromQuery('include'),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        ErrorBag::check($errorBag?->bag ?? []);

        throw new CoreException(ErrorBag::make(
            __('core::errors.Could not find the requested resource.'),
            "A non-existing relationship was requested: $relation",
            ErrorBag::paramsFromQuery('include'),
            Response::HTTP_BAD_REQUEST
        )->bag);
    }
}
