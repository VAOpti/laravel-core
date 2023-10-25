<?php

namespace VisionAura\LaravelCore\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;
use VisionAura\LaravelCore\Structs\ErrorStruct;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class CoreException extends \Exception
{

    /**
     * @param  array<ErrorStruct>  $errorBag
     * @param  string              $message
     * @param  int                 $code
     * @param  Throwable|null      $previous
     */
    public function __construct(array $errorBag, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        return ErrorBag::render($errorBag);
    }
}
