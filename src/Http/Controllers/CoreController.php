<?php

namespace VisionAura\LaravelCore\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\InvalidRelationException;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;
use VisionAura\LaravelCore\Http\Enums\QueryTypeEnum;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resolvers\FilterResolver;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;
use VisionAura\LaravelCore\Interfaces\RelationInterface;
use VisionAura\LaravelCore\Support\Facades\RequestFilter;
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

    public function __construct(bool $queriesRelationship = false)
    {
        try {
            $this->validateProperty($this->model ?? null, RelationInterface::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        }

        $this->checkErrors();

        /** @var RelationInterface $model */
        $model = new $this->model();
        if ($queriesRelationship) {
            $uri = Str::of(request()->path());
            if ($uri->contains('relationships/')) {
                $relation = $uri->after('relationships/');
                $relation = $relation->contains('/') ? $relation->before('/')->value() : $relation->value();

                $relation = $model->verifyRelation($relation) ? $relation : null;
            }
        }

        $relation ??= null;
        app()->bind('filter', function () use ($relation) {
            return new FilterResolver($relation ? (new $this->model())->getRelated($relation) : new $this->model());
        });
    }

    /**
     * @throws CoreException
     * @throws InvalidStatusCodeException
     */
    public function index(CoreRequest $request): GenericCollection|JsonResponse
    {
        $this->authorize('viewAny', $this->model);

        $request = $this->resolveRequestFrom($request);

        $this->checkErrors();

        return $this->apiResponse(new $this->model(), $request)->collection();
    }

    /**
     * @throws CoreException
     */
    public function indexRelation(CoreRequest $request, string $id, string $relation): GenericCollection|JsonResponse
    {
        $model = $this->resolveModelFrom($id);

        if (! $model instanceof RelationInterface) {
            $this->getErrors()->push(
                title: __('core::errors.Server error'),
                description: 'Can not resolve the provided relation.',
                source: request()->path(),
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $this->checkErrors();

        $relation = $model->resolveRelation($relation);
        $related = $model->getRelated($relation)
            ?: throw new InvalidRelationException(detail: 'The specified relation does not exist.', source: request()->path());

        $this->authorize('viewAny', $related);

        $ids = $model->{$relation}->pluck($related->getKeyName())->all();

        RequestFilter::addClause(value: $ids, type: QueryTypeEnum::WHERE_IN, attribute: $related->getKeyName());

        return $this->apiResponse($related, $request)->collection();
    }

    /**
     * @throws CoreException
     * @throws InvalidStatusCodeException
     */
    public function show(CoreRequest $request, string $id): GenericResource|JsonResponse
    {
        $request = $this->resolveRequestFrom($request);

        try {
            $this->validateProperty($this->model ?? null, Model::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        }

        $this->checkErrors();

        return $this->apiResponse(new $this->model(), $request)->from($id)->resource();
    }

    public function delete(string $id): JsonResponse
    {
        $this->validateProperty($this->repository ?? null, CoreRepository::class);

        if (method_exists($this->repository, 'beforeDelete')) {
            (new $this->repository())->beforeDelete();
        }

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

        if (! is_a($property, $subClassOf, true)) {
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