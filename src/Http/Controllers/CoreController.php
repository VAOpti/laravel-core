<?php

namespace VisionAura\LaravelCore\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use Ramsey\Collection\Exception\InvalidPropertyOrMethod;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;
use Illuminate\Http\Response;
use VisionAura\LaravelCore\Enums\QueryTypeEnum;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\InvalidRelationException;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;
use VisionAura\LaravelCore\Exceptions\MutatingNotAllowedException;
use VisionAura\LaravelCore\Http\Repositories\CoreRepository;
use VisionAura\LaravelCore\Http\Requests\CoreRequest;
use VisionAura\LaravelCore\Http\Resolvers\FilterResolver;
use VisionAura\LaravelCore\Http\Resources\GenericCollection;
use VisionAura\LaravelCore\Http\Resources\GenericResource;
use VisionAura\LaravelCore\Interfaces\MutableInterface;
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

    protected string $routeKey = '';

    public function __construct(bool $queriesRelationship = false)
    {
        $model = $this->getModel();

        $relation = null;
        if ($queriesRelationship) {
            $uri = Str::of(request()->path());
            if ($uri->contains('relationships/')) {
                $relation = $uri->after('relationships/');
                $relation = $relation->contains('/') ? $relation->before('/')->value() : $relation->value();

                $relation = $model->verifyRelation($relation) ? $relation : null;
            }
        }

        app()->singleton('filter', function () use ($relation, $model) {
            return new FilterResolver($relation ? $model->getRelated($relation) : new $this->model());
        });
    }

    public function getModel(): Model&RelationInterface
    {
        try {
            $this->validateProperty($this->model ?? null, RelationInterface::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        }

        $repository = $this->getRepository();

        $this->checkErrors();

        return $repository->getModel();
    }

    public function getRepository(): CoreRepository
    {
        try {
            $this->validateProperty($this->repository ?? null, CoreRepository::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        }

        $this->checkErrors();

        try {
            $repository = app($this->repository);
        } catch (BindingResolutionException $e) {
            // The repository was not initialized yet.
            return new $this->repository(new $this->model());
        }

        return $repository;
    }

    public function getRequest(): CoreRequest
    {
        return $this->resolveRequestFrom(request());
    }

    /**
     * @throws CoreException
     * @throws AuthorizationException|InvalidStatusCodeException
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
     * @throws AuthorizationException|InvalidStatusCodeException
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

    public function storeRules(CoreRequest $request): /*JsonResource*/ Response
    {
        return response('The store rules endpoint is not implemented yet. This endpoint will contain the rules that are applicable when storing a resource', Response::HTTP_NOT_IMPLEMENTED);
    }

    public function updateRules(CoreRequest $request): /*JsonResource*/ Response
    {
        return response('The update rules endpoint is not implemented yet. This endpoint will contain the rules that are applicable when updating a resource', Response::HTTP_NOT_IMPLEMENTED);
    }

    /** @throws MutatingNotAllowedException */
    public function update(CoreRequest $request, string $id): GenericResource
    {
        $model = $this->getModel();
        $this->verifyMuted($model);

        $repository = $this->getRepository();
        $request = $this->resolveRequestFrom($request);

        return $repository->update($request, $model);
    }

    /** @throws MutatingNotAllowedException */
    public function delete(string $id): JsonResponse
    {
        $model = $this->getModel();
        $this->verifyMuted($model);

        $repository = $this->getRepository();

        if (method_exists($repository, 'beforeDelete')) {
            $repository->beforeDelete();
        }

        if (! ($model = $this->resolveModelFrom($id))) {
            return $this->getErrors()->build();
        }

        $model->delete();

        return response()->json(status: 204);
    }

    public function resolveModelFrom(string $id): ?Model
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

    public function getRouteKey(): string
    {
        return $this->routeKey;
    }

    public function setRouteKey(string $name): void
    {
        $this->routeKey = $name;
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

    /**
     * @throws CoreException
     * @throws InvalidStatusCodeException
     */
    private function resolveRequestFrom(CoreRequest|Request $baseRequest): CoreRequest
    {
        try {
            $this->validateProperty($this->request ?? null, CoreRequest::class);
        } catch (InvalidPropertyOrMethod $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        } catch (ClassNotFoundError $error) {
            $this->getErrors()->push(__('core::errors.Server error'), $error->getMessage());
        }

        $this->checkErrors();

        /** @var CoreRequest $request */
        $request = $this->request::createFrom($baseRequest);
        $request->setContainer(app())
            ->setRedirector(app(Redirector::class))
            ->validateResolved();

        return $request;
    }

    /** @throws MutatingNotAllowedException */
    private function verifyMuted(Model $model): self
    {
        if ($model instanceof MutableInterface && $model->isMuted()) {
            throw new MutatingNotAllowedException();
        }

        return $this;
    }
}
