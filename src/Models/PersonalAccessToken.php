<?php

namespace VisionAura\LaravelCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    use HasUuids;
}