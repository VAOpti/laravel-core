<?php

namespace VisionAura\LaravelCore\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Traits\ApiResponse;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class CoreController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, HasErrorBag, ApiResponse;

    /** @var class-string $model */
    public string $model;

    /** @var class-string $repository */
    public string $repository;

    /** @var class-string $request */
    public string $request;

    /**
     * @throws CoreException
     * @throws InvalidStatusCodeException
     */
    public function index(CoreRequest $request): GenericCollection|JsonResponse
    {
        if (! ($request = $this->resolveRequestFrom($request)) && $this->hasErrors()) {
            return $this->getErrors()->build();
        }

        try {
            $this->validateProperty($this->model ?? null, Model::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        }

        $this->checkErrors();

        return $this->apiResponse(new $this->model())->collection();
    }

    /**
     * @throws CoreException
     */
    public function indexRelation(CoreRequest $request, string $id, string $relation): GenericCollection|JsonResponse
    {
        if (! ($model = $this->resolveModelFrom($id)) && $this->hasErrors()) {
            return $this->getErrors()->build();
        }

        if (! $model instanceof RelationInterface && ! $model instanceof Model) {
            return $this->error(
                __('core::errors.Server error'),
                'Can not resolve the provided relation.',
                code: Response::HTTP_NOT_IMPLEMENTED
            );
        }

        $relation = $model->resolveRelation($relation);

        return new GenericCollection($model->load($relation)->getRelation($relation));
    }

    public function show(CoreRequest $request, string $id): GenericResource|JsonResponse
    {
        if (! ($model = $this->resolveModelFrom($id)) && $this->hasErrors()) {
            return $this->getErrors()->build();
        }

        return new GenericResource($model);
    }

    public function delete(string $id): JsonResponse
    {
        if (! ($model = $this->resolveModelFrom($id)) && $this->hasErrors()) {
            return $this->getErrors()->build();
        }

        $model->delete();

        return response()->json(status: 204);
    }

    /**
     * @param  string|null  $property
     * @param  string       $subClassOf
     *
     * @return void
     * @throws InvalidPropertyOrMethod
     * @throws ClassNotFoundError
     */
    private function validateProperty(?string $property, string $subClassOf): void
    {
        $invalidPropertyException = new InvalidPropertyOrMethod(sprintf("The $property class for the %s is invalid.",
            static::class));

        if (! isset($property)) {
            // TODO: Deduct the name from the controller class-string
            throw $invalidPropertyException;
        }

        if (! class_exists($property)) {
            throw new ClassNotFoundError("The class $property does not exist.", $invalidPropertyException);
        }

        if (! is_subclass_of((new $property()), $subClassOf)) {
            throw $invalidPropertyException;
        }
    }

    private function resolveModelFrom(string $id): ?Model
    {
        try {
            $this->validateProperty($this->model ?? null, Model::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());

            return null;
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());

            return null;
        }

        /** @var Model $model */
        $model = new $this->model();

        return $model->where($model->getKeyName(), $id)->firstOrFail();
    }

    private function resolveRequestFrom(CoreRequest $baseRequest): ?CoreRequest
    {
        try {
            $this->validateProperty($this->request ?? null, CoreRequest::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());

            return null;
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());

            return null;
        }

        /** @var CoreRequest $request */
        $request = $this->request::createFrom($baseRequest);
        $request->setContainer(app())
            ->setRedirector(app(Redirector::class))
            ->validateResolved();

        return $request;
    }
}