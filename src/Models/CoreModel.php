<?php

namespace VisionAura\LaravelCore\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Traits\Relations;

class CoreModel extends Model implements RelationInterface
{
    use HasUuids, Relations;

    /** Get the table associated with the model. */
    public static function table(): string
    {
        return (new static())->getTable();
    }

    /** Qualify the given column name by the model's table. */
    public static function qualify(string $column): string
    {
        return (new static())->qualifyColumn($column);
    }
}