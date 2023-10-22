<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Http\JsonResponse;

trait HttpResponses
{
    protected function error(
        string $title = '',
        string $detail = null,
        string $source = null,
        int $code = 503
    ): JsonResponse {
        return response()->json([
            'error' => [
                'status' => $code,
                'source' => $source,
                'title'  => $title,
                'detail' => $detail,
            ]
        ]);
    }
}
