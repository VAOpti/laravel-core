<?php

namespace VisionAura\LaravelCore\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvalidRelationException extends CoreException
{
    public function __construct(
        string $detail,
        string $source,
        ?string $title = null,
        int $code = Response::HTTP_BAD_REQUEST,
        ?Throwable $previous = null
    ) {
        $this->errorBag = ErrorBag::make(
            title: $title ?? __('core::errors.Could not find the requested resource.'),
            description: $detail,
            source: $source,
            status: $code
        )->bag;

        parent::__construct($this->errorBag, $detail, $code, $previous);
    }
}
