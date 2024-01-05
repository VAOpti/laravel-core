<?php

namespace VisionAura\LaravelCore\Support\Relations;

class MorphTarget
{
    public function __construct(
        /** @var class-string $target */
        protected string $target,
    ) {
        //
    }

    public function getTarget()
    {
        return new $this->target();
    }
}