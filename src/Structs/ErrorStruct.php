<?php

namespace VisionAura\LaravelCore\Structs;

final readonly class ErrorStruct
{
    public function __construct(
        public string $title,
        public string $detail,
        public string $source,
        public int $status,
    )
    {
        //
    }
}