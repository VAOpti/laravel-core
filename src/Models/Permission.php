<?php

namespace VisionAura\LaravelCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasUuids;

    protected $connection = 'mysql';
}