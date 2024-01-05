<?php

namespace VisionAura\LaravelCore\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class JsonApiValidationException extends ValidationException
{
    use HasErrorBag;

    public function render($request): JsonResponse
    {
        /** @var array{string: array<int, string>} $messages */
        $messages = $this->validator->errors()->getMessages();

        foreach ($messages as $source => $error) {
            $source = str_replace('.', '/', $source);
            foreach ($error as $errorMessage) {
                $this->getErrors()->push(
                    __('core::errors.The given data was invalid.'),
                    $errorMessage,
                    $source,
                    Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return $this->getErrors()->build();
    }
}
