<?php

namespace VisionAura\LaravelCore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Controllers\CoreController;
use VisionAura\LaravelCore\Http\Repositories\AbstractCoreRepository;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Route::macro('crud', function ($name, $controller) {
            if (($controller = new $controller()) instanceof CoreController) {
                if (isset($controller->repository) && isset($controller->request)) {
                    $repository = new $controller->repository();

                    if (is_subclass_of($repository, AbstractCoreRepository::class)) {
                        if (method_exists($repository, 'store')) {
                            Route::post("$name/", "{$controller->repository}@store");
                        }

                        if (method_exists($repository, 'update')) {
                            $key = Str::of($name)->singular()->value();
                            Route::match(['put', 'patch'], "$name/{{$key}}", "{$controller->repository}@update");
                        }
                    }
                }
            }
        });
    }
}