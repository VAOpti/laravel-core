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
            foreach (CoreResource::mapIncludes($resource->resource) as $relation => $resourceIncludes) {
                foreach ($resourceIncludes as $resourceInclude) {
                    if (! array_key_exists($relation, $includes) && ! $resourceInclude) {
                        $includes[$relation] = [];

                        continue;
                    }

                    if (! $resourceInclude) {
                        continue;
                    }

                    $includes[$relation][] = $resourceInclude;
                }
            }
        }

        return $includes;
    }
}
