<?php

namespace VisionAura\LaravelCore\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use VisionAura\LaravelCore\Http\Controllers\CoreController;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'core');

        $this->publishes([
            __DIR__.'/../config/permission.php' => config_path('permission.php'),
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ]);

        /* Macro's */
        Route::macro('jsonAPI', function ($name, $controller) {
            $controllerString = $controller;

            if (($controller = new $controller()) instanceof CoreController) {
                if (isset($controller->request)) {
                    $uri = "$name";
                    $key = Str::of($name)->singular()->value();
                    $selfUri = "$name/{{$key}}";

                    if (method_exists($controller, 'index')) {
                        Route::get($uri, "{$controllerString}@index");

                        return;
                    }

                    if (method_exists($controller, 'show')) {
                        Route::get($selfUri, "{$controllerString}@show");

                        return;
                    }

                    if (method_exists($controller, 'indexRelation')) {
                        Route::get("$name/{{$key}}/relationships/{relation}", "{$controllerString}@indexRelation");

                        return;
                    }

                    if ((isset($controller->model) && ($model = new $controller->model()) instanceof Model)
                        && (isset($controller->repository) && ($repository = new $controller->repository($model)) instanceof CoreRepository)
                    ) {
                        app()->bind($controller->repository, function () use ($controller, $model) {
                            return new $controller->repository($model);
                        });

                        if (method_exists($repository, 'store')) {
                            Route::post($uri, "{$controller->repository}@store");

                            return;
                        }

                        if (method_exists($repository, 'update')) {
                            Route::match(['put', 'patch'], $selfUri, "{$controller->repository}@update");

                            return;
                        }

                        if (method_exists($repository, 'delete')) {
                            Route::delete($selfUri, "{$controller->repository}@delete");

                            return;
                        }

                        if (method_exists($controller, 'delete')) {
                            Route::delete($selfUri, "{$controllerString}@delete");
                        }
                    }
                }
            }
        });

        /**
         * @fixme: Does not work properly
         * Takes an array, recursively loops through it and places every array it
         * finds on the root level, creating a one dimensional array.
         */
        Arr::macro('flattenSingle', function (array $arr) {
            $result = [];

            foreach ($arr as $key => $val) {
                if (! $val) {
                    $result[ $key ] = $val;

                    continue;
                }

                foreach ($val as $subKey => $subVal) {
                    if (is_array($subVal)) {
                        $recursive = Arr::flattenSingle([is_int($subKey) ? $key : "$key.$subKey" => $subVal]);
                        $result = array_merge($result, $recursive);
                        unset($val[ $subKey ]);

                        continue;
                    }
                }

                if ($val) {
                    $result[ $key ][] = $val;
                }
            }

            return $result;
        });

        Arr::macro('mapRecursive', function (array $arr, callable $callback) {
            $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));
            foreach ($ritit as $leaf) {
                foreach (range(0, $ritit->getDepth()) as $depth) {
                    $key = $ritit->getSubIterator($depth)->key();
                    $value = $ritit->getSubIterator($depth)->current();

                    call_user_func_array($callback, [$key, $value, $depth]);
                }
            }
        });

        /** If the array only contains 1 value, it returns that. Otherwise, returns the whole array. */
        Arr::macro('unwrapSingle', function (array $arr) {
            return count($arr) === 1 ? head($arr) : $arr;
        });
    }
}
