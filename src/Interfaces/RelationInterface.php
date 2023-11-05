<?php

namespace VisionAura\LaravelCore\Interfaces;

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
}