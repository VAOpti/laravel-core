<?php

namespace VisionAura\LaravelCore\Support\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Facade;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;
use VisionAura\LaravelCore\Http\Enums\FilterOperatorsEnum;
use VisionAura\LaravelCore\Http\Enums\QueryTypeEnum;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resolvers\FilterResolver;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Models\CoreModel;

/**
 * @method static RelationInterface getModel()
 * @method static CoreRepository getRepository()
 * @method static CoreRequest getRequest()
 * @method static string getRouteKey()
 */
class RequestController extends Facade
{
    /** @inheritdoc */
    protected static function getFacadeAccessor(): string
    {
        return 'requestController';
    }
}
