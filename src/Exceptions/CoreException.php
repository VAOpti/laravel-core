<?php

namespace VisionAura\LaravelCore\Exceptions;

use Illuminate\Http\JsonResponse;
use Throwable;
use VisionAura\LaravelCore\Structs\ErrorStruct;

final class CoreException extends \Exception
{
    private array $errorBag;

    /**
     * @param  array<ErrorStruct>  $errorBag
     * @param  string              $message
     * @param  int                 $code
     * @param  Throwable|null      $previous
     */
    public function __construct(array $errorBag, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errorBag = $errorBag;
    }

    public function render(): JsonResponse
    {
        return ErrorBag::render($this->errorBag);
    }
}
