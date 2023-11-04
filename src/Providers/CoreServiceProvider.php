<?php

namespace VisionAura\LaravelCore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Http\Controllers\CoreController;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'core');

        Route::macro('jsonAPI', function ($name, $controller) {
            $controllerString = $controller;

            if (($controller = new $controller()) instanceof CoreController) {
                if (isset($controller->repository) && isset($controller->request)) {
                    $repository = new $controller->repository();

                    $uri = "$name";
                    $key = Str::of($name)->singular()->value();
                    $selfUri = "$name/{{$key}}";

                    if(count($groupStack = Route::getGroupStack()) > 2) {
                        $prefix = Str::of($groupStack[2]['prefix']);
                        if ($prefix->contains('v1/')) {
                            $parentKey = $prefix->remove('v1/')->singular();

                            $selfUri = "{{$parentKey}}/relationships/{{$key}}";
                        }
                    }

                    if (method_exists($controller, 'index')) {
                        Route::get($uri, "{$controllerString}@index");
                    }

                    if (method_exists($controller, 'show')) {
                        Route::get($selfUri, "{$controllerString}@show");
                    }

                    if (is_subclass_of($repository, CoreRepository::class)) {
                        if (method_exists($repository, 'store')) {
                            Route::post($uri, "{$controller->repository}@store");
                        }

                        if (method_exists($repository, 'update')) {
                            Route::match(['put', 'patch'], $selfUri, "{$controller->repository}@update");
                        }

                        if (method_exists($repository, 'delete')) {
                            Route::delete($selfUri, "{$controller->repository}@delete");
                        }
                    }
                }
            }
        });
    }
}