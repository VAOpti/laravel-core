<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;

trait HasErrorBag
{
    private ErrorBag $errors;

    protected function getErrors(): ErrorBag
    {
        return $this->errors ?? $this->errors = new ErrorBag();
    }

    protected function error(
        string $title = '',
        string $detail = null,
        string $source = null,
        int $code = Response::HTTP_SERVICE_UNAVAILABLE
    ): JsonResponse {
        return $this->getErrors()->push($title, $detail, $source, $code)->build();
    }

    protected function hasErrors(): bool
    {
        return (bool) $this->getErrors()->bag;
    }

    /**
     * @throws CoreException
     */
    protected function checkErrors(): true
    {
        return ErrorBag::check($this->getErrors()->bag);
    }
}
