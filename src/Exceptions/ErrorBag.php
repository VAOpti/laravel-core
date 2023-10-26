<?php

namespace VisionAura\LaravelCore\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Structs\ErrorStruct;

class ErrorBag
{
    /** @var array<ErrorStruct> */
    public array $bag = [];

    public static function make(
        string $title,
        string $description,
        ?string $source = null,
        int $status = Response::HTTP_NOT_IMPLEMENTED
    ): self {
        return (new self())->push($title, $description, $source, $status);
    }

    /**
     * @throws InvalidStatusCodeException
     */
    public function push(
        string $title,
        string $description,
        ?string $source = null,
        int $status = Response::HTTP_NOT_IMPLEMENTED
    ): self {
        if ((! ($status >= 400)) || (! ($status <= 599))) {
            throw new InvalidStatusCodeException();
        }

        $this->bag[] = new ErrorStruct($title, $description, $source, $status);

        return $this;
    }

    public function build(): JsonResponse
    {
        return self::render($this->bag);
    }

    /**
     * @param  array<ErrorStruct>  $errorBag
     *
     * @return JsonResponse
     */
    public static function render(array $errorBag): JsonResponse
    {
        $response = [];

        foreach ($errorBag as $error) {
            $response[] = $error->toArray();
        }

        return response()->json([
            'errors' => $response,
        ], self::filterGeneralStatusCode($errorBag));
    }

    /**
     * @param  array<ErrorStruct>  $errorBag
     *
     * @return bool
     * @throws CoreException
     */
    public static function check(array $errorBag): false
    {
        if ($errorBag) {
            throw new CoreException($errorBag);
        }

        return false;
    }

    /**
     * @param  array<ErrorStruct>  $errorBag
     *
     * @return int
     */
    private static function filterGeneralStatusCode(array $errorBag): int
    {
        $statusCodes = [];

        foreach ($errorBag as $error) {
            if (! in_array($error->status, $statusCodes)) {
                $statusCodes[] = $error->status;
            }
        }

        if (! $statusCodes) {
            return Response::HTTP_BAD_REQUEST;
        }

        sort($statusCodes);

        return $statusCodes[ 0 ];
    }
}
