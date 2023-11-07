<?php

namespace VisionAura\LaravelCore\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CoreResource extends JsonResource
{
    protected string $type;

    /** @var array<mixed> $attributes */
    protected array $attributes = [];

    /** @var array<mixed> $relations */
    protected array $relations = [];

    /** @var array<mixed> $includes */
    protected array $includes = [];

    /** @var array<string, Carbon|null> $timestamps */
    protected array $timestamps = [];

    /** @inheritDoc */
    public function __construct(mixed $resource)
    {
        parent::__construct($resource);

        $this->setAttributes();

        if ($resource instanceof Collection && $resource->isEmpty()) {
            return;
        }

        $this->setType()->setTimestamps()->setRelations()->setIncludes();
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type'          => $this->type,
            'id'            => $this->{$this->resource->getKeyName()},
            'attributes'    => $this->attributes,
            'timestamps'    => $this->when((bool) $this->timestamps, $this->timestamps),
            'relationships' => $this->when((bool) $this->relations, $this->relations),
        ];
    }

    /** @inheritdoc */
    public function with(Request $request): array
    {
        if (! $this->includes) {
            return [];
        }

        return [
            'included' => $this->includes,
        ];
    }

    /** @return array<mixed> */
    public static function mapIncludes(Model $model): array
    {
        $includes = [];

        /**
         * @var string  $name
         * @var Model[]|Collection|null $loadedRelations
         */
        foreach ($model->getRelations() as $name => $loadedRelations) {
            if (! $loadedRelations || ($loadedRelations instanceof Collection && $loadedRelations->isEmpty())) {
                $includes[ $name ][] = null;

                continue;
            }

            foreach ($loadedRelations as $relation) {
                $includes[ $name ][] = (new self($relation))->toArray(request());
            }
        }

        return $includes;
    }

    protected function setAttributes(): self
    {
        if ($this->resource instanceof Model) {
            $this->attributes = $this->resource->attributesToArray();

            $id = $this->resource->getKeyName();
            if (array_key_exists($id, $this->attributes)) {
                unset($this->attributes[$id]);
            }

            return $this;
        }

        $this->attributes = $this->resource;

        return $this;
    }

    protected function setType(): self
    {
        $className = get_class($this->resource);
        $className = substr($className, strrpos($className, '\\') + 1);
        $this->type = strtolower($className);

        return $this;
    }

    protected function setTimestamps(): self
    {
        if ($this->resource->timestamps) {
            foreach (['created_at', 'updated_at'] as $timestamp) {
                $this->timestamps[ $timestamp ] = $this->resource->{$timestamp};
                unset($this->attributes[ $timestamp ]);
            }

            if ($this->resource->hasCast('deleted_at')) {
                $this->timestamps[ 'deleted_at' ] = $this->resource->deleted_at;
                unset($this->attributes[ 'deleted_at' ]);
            }
        }

        return $this;
    }

    protected function setRelations(): self
    {
        if (! $this->resource instanceof Model) {
            return $this;
        }

        /**
         * @var string  $name
         * @var Model[] $loadedRelations
         */
        foreach ($this->resource->getRelations() as $name => $loadedRelations) {
            if (! $loadedRelations) {
                $this->relations[ $name ] = [];

                continue;
            }

            /** @var array{links:array{}, data:array<string, string>} $fields */
            $fields = [
                'links' => [],
                'data'  => []
            ];

            foreach ($loadedRelations as $relation) {
                $id = $relation->{$relation->getKeyName()};
                $fields[ 'data' ][] = [
                    'type' => $name,
                    'id'   => $id,
                ];
            }

            $this->relations[ $name ] = $fields;
        }

        return $this;
    }

    protected function setIncludes(): self
    {
        if (! $this->resource instanceof Model) {
            return $this;
        }

        static::mapIncludes($this->resource);

        return $this;
    }
}
