<?php

namespace VisionAura\LaravelCore\Structs;

final readonly class ParentChildRelationStruct
{
    public function __construct(
        public string $owner,
        public string $foreign
    )
    {
        //
    }
}