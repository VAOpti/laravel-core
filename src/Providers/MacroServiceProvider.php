<?php

namespace VisionAura\LaravelCore\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Arr::macro('mapRecursive', function (array $arr, callable $callable) {
            call_user_func_array($callable, [1, 2]);
        });
    }
}