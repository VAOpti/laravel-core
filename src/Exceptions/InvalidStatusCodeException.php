<?php

namespace VisionAura\LaravelCore\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvalidStatusCodeException extends \Exception
{
    public function __construct(
        string $message = 'An error status code was provided which was smaller than 400 or greater than 599',
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
