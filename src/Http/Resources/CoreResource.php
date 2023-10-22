<?php

namespace VisionAura\LaravelCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class CoreResource extends JsonResource
{
    protected string $type;

    /** @var array<mixed> $attributes */
    protected array $attributes = [];

    /** @var array<string, Carbon|null> $timestamps */
    protected array $timestamps = [];

    /** @inheritDoc */
    public function __construct(mixed $resource)
    {
        parent::__construct($resource);

        $this->attributes = $this->resource->toArray();

        $this->setType()->setTimestamps();
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type'       => $this->type,
            'id'         => $this->{$this->resource->getKeyName()},
            'attributes' => $this->attributes,
            'timestamps' => $this->when((bool) $this->timestamps, $this->timestamps)
        ];
    }

    protected function setType(): self
    {
        $className = get_class($this->resource);
        $className = substr($className, strrpos($className, '\\') + 1);;
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
}
