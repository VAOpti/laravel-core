<?php

namespace VisionAura\LaravelCore\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
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
     * @param  array<ErrorStruct>|array{}|null  $errorBag
     *
     * @return true
     * @throws CoreException
     */
    public static function check(?array $errorBag): true
    {
        if ($errorBag) {
            throw new CoreException($errorBag);
        }

        return true;
    }

    /**
     * Get the parameters from the URI, and format them back to a string. For the source in error messages.
     *
     * @param  string  $parameter  The name of the key that needs to be filtered (include, page, fields, filter, etc.)
     *
     * @return string|null
     */
    public static function paramsFromQuery(string $parameter): ?string
    {
        $arguments = request()->query->filter($parameter, options: ['flags' => \FILTER_FORCE_ARRAY]);

        if (! $arguments) {
            return null;
        }

        $string = '';

        foreach (array_flatten($arguments, '][') as $key => $argument) {
            if (is_string($key)) {
                $string .= $parameter."[{$key}]={$argument} | ";
            } else {
                $string .= "$parameter={$argument} | ";
            }
        }

        return Str::of($string)->replaceLast(' | ', '')->value();
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
