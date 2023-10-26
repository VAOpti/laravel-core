<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;

trait HasErrorBag
{
    private ErrorBag $errors;

    public function __construct()
    {
        $this->errors = new ErrorBag();
    }

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
        return $this->errors->push($title, $detail, $source, $code)->build();
    }

    /**
     * @throws CoreException
     */
    protected function hasErrors(): true
    {
        return ErrorBag::check($this->errors->bag);
    }
}
