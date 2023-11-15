<?php

namespace VisionAura\LaravelCore\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;
use VisionAura\LaravelCore\Structs\ParentChildRelationStruct;

trait Relations
{
    /** @inheritdoc */
    public function resolveRelation(string $relation): string
    {
        assert($this instanceof Model, 'This trait should only be used on instances of '.Model::class);

        if (str_contains($relation, '.')) {
            $relations = explode('.', $relation);
            $parent = $relations[ 0 ];

            if ($this->isRelation($parent)) {
                $this->{$parent}()->getRelated()->resolveRelation(implode('.', array_splice($relations, 1)));
            }
        }

        if ($this->isRelation($relation)
            || (isset($parent) && $this->isRelation($parent))
        ) {
            return $relation;
        }

        $relation = Str::of($relation);
        $plural = (clone $relation)->plural()->value();
        $singular = $relation->singular()->value();

        foreach ([$plural, $singular] as $guess) {
            if ($this->isRelation($guess)) {
                $errorBag = ErrorBag::make(
                    __('core::errors.Could not find the requested resource.'),
                    "Did you mean the following relation: '$guess'?",
                    ErrorBag::paramsFromQuery('include'),
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        ErrorBag::check($errorBag?->bag ?? []);

        throw new CoreException(ErrorBag::make(
            __('core::errors.Could not find the requested resource.'),
            "A non-existing relationship was requested: $relation",
            ErrorBag::paramsFromQuery('include'),
            Response::HTTP_BAD_REQUEST
        )->bag);
    }

    /** @inheritdoc */
    public function resolveDependentKeys(string $relation): array|string|null
    {
        if (! method_exists($this, $relation) && ! method_exists($this->$relation(), 'getForeignKeyName')) {
            return null;
        }

        $foreignKey = $this->$relation()->getForeignKeyName();

        if (method_exists($this->$relation(), 'getMorphType')) {
            return [$this->$relation()->getMorphType(), $foreignKey];
        }

        if (method_exists($this->$relation(), 'getOwnerKeyName')) {
            return $this->$relation()->getOwnerKeyName();
        }

        return $this->$relation()->getForeignKeyName();
    }

    public function resolveQualifiedDependentKeys(string $relation): ParentChildRelationStruct|null
    {
        if (! method_exists($this, $relation) && ! method_exists($this->$relation(), 'getQualifiedForeignKeyName')) {
            return null;
        }

        if (method_exists($this->$relation(), 'getQualifiedOwnerKeyName')) {
            return new ParentChildRelationStruct($this->$relation()->getQualifiedForeignKeyName(), $this->$relation()->getQualifiedOwnerKeyName());
        }

        if (method_exists($this->$relation(), 'getQualifiedParentKeyName')) {
            return new ParentChildRelationStruct($this->$relation()->getQualifiedParentKeyName(), $this->$relation()->getQualifiedForeignKeyName());
        }

        // TODO: The morph type?
        return null;
    }

    /** @inheritdoc */
    public function getForeignKeyOwner(string $relation): ?Model
    {
        if (! method_exists($this, $relation)) {
            return null;
        }

        /** @var Relation $relation */
        $relation = $this->{$relation}();

        return match (true) {
            ($relation instanceof HasOneOrMany) => $relation->getRelated(),
            ($relation instanceof BelongsTo),
            ($relation instanceof BelongsToMany) => $this,
        };
    }
}
