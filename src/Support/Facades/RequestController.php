<?php

namespace VisionAura\LaravelCore\Support\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Interfaces\RelationInterface;

/**
 * @method static Model|RelationInterface getModel()
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
