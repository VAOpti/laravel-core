<?php

namespace VisionAura\LaravelCore\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Env;
use Throwable;
use VisionAura\LaravelCore\Structs\ErrorStruct;

class CoreException extends \Exception
{
    public array $errorBag;

    /**
     * @param  array<ErrorStruct>  $errorBag
     * @param  string              $message
     * @param  int                 $code
     * @param  Throwable|null      $previous
     */
    public function __construct(array $errorBag, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $this->errorBag = $errorBag;

        parent::__construct($this->getFirstDetail(), $errorBag[ 0 ]->status, $previous);
    }

    public function render(): JsonResponse|null
    {
        if (Env::get('APP_ENV') !== 'local') {
            return ErrorBag::render($this->errorBag);
        }

        return null;
    }

    public function getFirstDetail(): string
    {
        return $this->errorBag[ 0 ]->detail;
    }
}
