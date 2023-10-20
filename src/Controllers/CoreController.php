<?php

namespace VisionAura\LaravelCore\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Traits\HttpResponses;

class CoreController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, HttpResponses;

    /** @var class-string $model */
    public string $model;

    /** @var class-string $repository */
    public string $repository;

    /** @var class-string $request */
    public string $request;

    public function show(Request $request, string $id): JsonResponse
    {
        $model = $this->resolveModelFrom($id);

        return $this->success($model);
    }

    private function validateProperty(?string $property, string $subClassOf): void
    {
        $invalidPropertyException = new InvalidPropertyOrMethod(sprintf("The $subClassOf class for the %s was invalid.",
            static::class), 501);

        if (! isset($property)) {
            // Deduct the name from the controller class-string
            throw $invalidPropertyException;
        }

        if (! class_exists($property)) {
            throw new ClassNotFoundError("The class $property does not exist.", $invalidPropertyException);
        }

        if (! is_subclass_of((new $property()), $subClassOf)) {
            throw $invalidPropertyException;
        }
    }

    private function resolveModelFrom(string $id): Model
    {
        $this->validateProperty($this->model ?? null, Model::class);

        /** @var Model $model */
        $model = new $this->model();

        return $model->where($model->getKeyName(), $id)->firstOrFail();
    }

    private function resolveRequestFrom(CoreRequest $baseRequest): CoreRequest
    {
        $this->validateProperty($this->request ?? null, CoreRequest::class);

        /** @var CoreRequest $request */
        $request = $this->request::createFrom($baseRequest);
        $request->setContainer(app())
            ->setRedirector(app(Redirector::class))
            ->validateResolved();

        return $request;
    }
}