<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;
use VisionAura\LaravelCore\Structs\ErrorStruct;

trait HttpResponses
{
    /** @var ErrorStruct[] $errors */
    private array $errors;

    /** @throws InvalidStatusCodeException */
    protected function error(
        string $title = '',
        string $detail = null,
        string $source = null,
        int $code = Response::HTTP_SERVICE_UNAVAILABLE
    ): JsonResponse {
        return $this->pushError($title, $detail, $source, $code)->buildErrors();
    }

    protected function buildErrors(): JsonResponse
    {
        $response = [];

        foreach ($this->errors as $error) {
            $response[] = $this->toArray($error);
        }

        return response()->json([
            'errors' => $response,
        ], $this->filterGeneralStatusCode());
    }

    protected function hasErrors(): bool
    {
        return (bool) count($this->errors);
    }

    /** @throws InvalidStatusCodeException */
    protected function pushError(
        string $title = '',
        string $detail = null,
        string $source = null,
        int $code = Response::HTTP_SERVICE_UNAVAILABLE
    ): self {
        if ((! ($code >= 400)) || (! ($code <= 599))) {
            throw new InvalidStatusCodeException();
        }

        $this->errors[] = new ErrorStruct($title, $detail, $source, $code);

        return $this;
    }

    private function filterGeneralStatusCode(): int
    {
        $statusCodes = [];

        foreach ($this->errors as $error) {
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

    /** @return array<string, int|string> */
    private function toArray(ErrorStruct $error): array
    {
        return [
            'status' => $error->status,
            'source' => [
                'pointer' => [$error->source]
            ],
            'title'  => $error->title,
            'detail' => $error->detail,
        ];
    }
}
