<?php

use Illuminate\Support\Str;

if (! function_exists('pluralizeModel')) {
    function pluralizeModel(\Illuminate\Database\Eloquent\Model $model): string {
        return (string) Str::of(class_basename($model))->plural()->lower();
    }
}