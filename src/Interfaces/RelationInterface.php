<?php

namespace VisionAura\LaravelCore\Interfaces;

use Illuminate\Database\Eloquent\Model;
use VisionAura\LaravelCore\Exceptions\CoreException;

interface RelationInterface
{
    /**
     * Makes sure the given relation is valid or guesses the name of the relation from the provided string.
     *
     * @param  string  $relation
     *
     * @return string The name of the relation
     * @throws CoreException
     */
    public function resolveRelation(string $relation): string;

    /**
     * Determine whether the foreign key exists on the parent of the child model.
     *
     * @param  string  $relation
     *
     * @return Model|null
     */
    public function getForeignKeyOwner(string $relation): ?Model;

    /**
     * Returns the foreign key or the morph type and morph ID if it's a polymorphic relationship.
     *
     * @param  string  $relation
     *
     * @return string[]|string|null
     */
    public function resolveDependentKeys(string $relation): array|string|null;
}