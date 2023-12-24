<?php

namespace VisionAura\LaravelCore\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Support\Facades\RequestController;

class ValidRelation implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $model = new (RequestController::getFacadeRoot()->model)();

        if (! $model instanceof RelationInterface) {
            throw new InvalidPropertyOrMethod('The ' . RequestController::getFacadeRoot()->model . ' class for the ' . get_class(RequestController::getFacadeRoot()) . ' is invalid');
        }

        if (! $model->verifyRelation($value)) {
            $fail('core::errors.Could not find the requested resource.')->translate();
        }
    }
}
