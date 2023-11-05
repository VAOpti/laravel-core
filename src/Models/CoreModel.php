<?php

namespace VisionAura\LaravelCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Traits\Relations;

class CoreModel extends Model implements RelationInterface
{
    use HasUuids, Relations;
}