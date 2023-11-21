<?php

namespace VisionAura\LaravelCore\Casts;

class CastPrimitives
{
    private array $availableCasts = [
        'bool', 'float', 'int'
    ];

    public function __construct(
        private readonly mixed $value,
        private readonly ?string $cast = null
    ) {
        //
    }

    public function cast(): mixed
    {
        if (! $this->cast) {
            return $this->value;
        }

        if (! in_array($this->cast, $this->availableCasts)) {
            return $this->value;
        }

        return match ($this->cast) {
            'bool' => $this->castBool(),
            'float' => $this->castFloat(),
            'int' => $this->castInt()
        };
    }

    public function castBool(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
    }

    public function castFloat(): float
    {
        return (float) filter_var($this->value, FILTER_VALIDATE_FLOAT);
    }

    public function castInt(): int
    {
        return (int) filter_var($this->value, FILTER_VALIDATE_INT);
    }
}