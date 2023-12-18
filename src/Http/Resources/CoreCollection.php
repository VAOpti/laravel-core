<?php

namespace VisionAura\LaravelCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;

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
            'meta' => [
                'count' => $this->collection->count()
            ]
        ];
    }

    /** @inheritdoc */
    public function with(Request $request): array
    {
        $includes = $this->getIncludes();

        if (! $includes) {
            return [];
        }

        return [
            'included' => $includes,
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

                    $includes[$relation][$resourceInclude['type'].$resourceInclude['id']] = $resourceInclude;
                }
            }
        }

        /** @var array<string, array<mixed>> $included */
        $includes = Arr::map($includes, function (array $included, string $type) {
            // The includes array contained ids as key, convert those to numeric keys.
            return array_values($included);
        });

        return $includes;
    }
}
