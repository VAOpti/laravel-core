<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HttpResponses
{
    protected function success(mixed $data = [], string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'Request was successful.',
            'message' => $message,
            'data'    => $this->formatResponseData($data)
        ], $code);
    }

    protected function error(string|array $data = [], string $message = null, int $code = 503): JsonResponse
    {
        return response()->json([
            'status'  => 'An error has occurred.',
            'message' => $message,
            'data'    => $this->formatResponseData($data)
        ], $code);
    }

    private function formatResponseData(mixed $data): ?array
    {
        if ($data instanceof Model) {
            return [((string) Str::of(get_class($data))->afterLast('\\')->lower()) => $data];
        }

        return Arr::wrap($data);
    }
}
