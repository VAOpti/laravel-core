<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Services\Response\ApiResponseService;

trait ApiResponse
{
    private ApiResponseService $responseService;

    public function apiResponse(Model $model): ApiResponseService
    {
        return $this->responseService ?? $this->responseService = new ApiResponseService($model);
    }
}