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

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'source' => [
                'pointer' => [$this->source]
            ],
            'title'  => $this->title,
            'detail' => $this->detail,
        ];
    }
}