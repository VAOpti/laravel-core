<?php

namespace VisionAura\LaravelCore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Controllers\CoreController;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'core');

        Route::macro('jsonAPI', function ($name, $controller) {
            $controllerString = $controller;

            if (($controller = new $controller()) instanceof CoreController) {
                if (isset($controller->repository) && isset($controller->request)) {
                    $repository = new $controller->repository();
                    $key = Str::of($name)->singular()->value();

                    if (method_exists($controller, 'index')) {
                        Route::get("$name", "{$controllerString}@index");
                    }

                    if (method_exists($controller, 'show')) {
                        Route::get("$name/{{$key}}", "{$controllerString}@show");
                    }

                    if (is_subclass_of($repository, CoreRepository::class)) {
                        if (method_exists($repository, 'store')) {
                            Route::post("$name", "{$controller->repository}@store");
                        }

                        if (method_exists($repository, 'update')) {
                            Route::match(['put', 'patch'], "$name/{{$key}}", "{$controller->repository}@update");
                        }

                        if (method_exists($repository, 'delete')) {
                            Route::delete("$name/{{$key}}", "{$controller->repository}@delete");
                        }
                    }
                }
            }
        });
    }
}