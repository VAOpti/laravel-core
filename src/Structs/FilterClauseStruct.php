<?php

namespace VisionAura\LaravelCore\Structs;

use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;

final readonly class FilterClauseStruct
{
    public function __construct(
        public mixed $value,
        public ?string $relation = null,
        public ?string $attribute = null,
        public string|FilterOperatorsEnum $operator = FilterOperatorsEnum::EQUALS,
    ) {
        //
    }

    public function resolveValue(): mixed
    {
        if (is_string($this->operator)) {
            // The operator is a custom scope function.
            return $this->value;
        }

        return match ($this->operator) {
            FilterOperatorsEnum::SEARCH => "%{$this->value}%",
            FilterOperatorsEnum::STARTS_WITH => "{$this->value}%",
            FilterOperatorsEnum::ENDS_WITH => "%{$this->value}",
            default => $this->value
        };
    }
}