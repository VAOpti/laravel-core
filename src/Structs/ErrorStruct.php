<?php

namespace VisionAura\LaravelCore\Structs;

use Symfony\Component\HttpFoundation\Response;

final readonly class ErrorStruct
{
    public function __construct(
        public string $title,
        public string $detail,
        public ?string $source = null,
        public int $status = Response::HTTP_NOT_IMPLEMENTED,
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