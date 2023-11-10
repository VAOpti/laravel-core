<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Http\Resolvers\ApiResponseResolver;

trait ApiResponse
{
    private ApiResponseResolver $responseResolver;

    public function apiResponse(Model $model): ApiResponseResolver
    {
        return $this->responseResolver ?? $this->responseResolver = new ApiResponseResolver($model);
    }
}