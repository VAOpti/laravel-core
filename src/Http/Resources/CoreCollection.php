<?php

namespace VisionAura\LaravelCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CoreCollection extends ResourceCollection
{
    /** @inheritDoc */
    public function toArray(Request $request): array
    {
        if ($this->collection->isEmpty()) {
            return [];
        }

        return [
            'data' => $this->collection,
            'included' => $this->getIncludes(),
            'meta' => [
                'count' => $this->collection->count()
            ]
        ];
    }

    protected function getIncludes(): array
    {
        $includes = [];

        /** @var JsonResource $resource */
        foreach ($this->collection as $resource) {
            $includes[] = CoreResource::mapIncludes($resource->resource);
        }

        return $includes;
    }
}
