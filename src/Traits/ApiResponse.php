<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resolvers\ApiResponseResolver;

trait ApiResponse
{
    private ApiResponseResolver $responseResolver;

    public function apiResponse(Model $model, CoreRequest $request): ApiResponseResolver
    {
        return $this->responseResolver ?? $this->responseResolver = new ApiResponseResolver($model, $request);
    }
}