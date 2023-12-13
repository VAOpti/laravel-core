<?php

namespace VisionAura\LaravelCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Role extends \Spatie\Permission\Models\Role
{
    use HasUuids;

    protected $connection = 'mysql';
}