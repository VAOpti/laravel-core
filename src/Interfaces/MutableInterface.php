<?php

namespace VisionAura\LaravelCore\Interfaces;

interface MutableInterface
{
    /** Checks if the fields on the model are allowed to be edited or not. */
    public function isMuted(): bool;
}
