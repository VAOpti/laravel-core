<?php

namespace VisionAura\LaravelCore\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CoreApplication extends CoreModel
{
    use SoftDeletes;

    protected $table = 'applications';
}