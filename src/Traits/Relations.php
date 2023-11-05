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
        assert($this instanceof Model);

        if ($this->isRelation($relation)) {
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
                    request()->getRequestUri(),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }
        
        ErrorBag::check($errorBag?->bag ?? []);

        throw new CoreException(ErrorBag::make(
            __('core::errors.Could not find the requested resource.'),
            'A non-existing relationship was requested.',
            request()->getRequestUri(),
            Response::HTTP_BAD_REQUEST
        )->bag);
    }
}