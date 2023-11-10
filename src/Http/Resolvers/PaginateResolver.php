<?php

namespace VisionAura\LaravelCore\Http\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\ErrorBag;

class PaginateResolver
{
    public bool $hasPagination = false;

    /** @var array{offset:int, limit:int} $params */
    public array $params = ['offset' => 0, 'limit' => 15];

    public function __construct(Model $model)
    {
        $pagination = request()->query->all('page');

        $this->hasPagination = (bool) $pagination;

        if (! $this->hasPagination) {
            return;
        }

        if (! Arr::has($pagination, 'offset')) {
            throw new CoreException(ErrorBag::make(
                __('core::errors.Pagination failed'),
                'The page query parameter must include the offset key.',
                sprintf("page%s", Str::of(request()->server->get('QUERY_STRING'))->between('&page', '&')->value()),
                Response::HTTP_BAD_REQUEST)->bag
            );
        }

        $this->params[ 'offset' ] = Arr::get($pagination, 'offset');

        if (Arr::has($pagination, 'limit')) {
            $this->params[ 'limit' ] = Arr::get($pagination, 'limit');

            return;
        }

        $this->params[ 'limit' ] = $model->getPerPage();
    }

    public function getPage(): int
    {
        return $this->getOffset() + 1;
    }

    public function getOffset(): int
    {
        return $this->params[ 'offset' ];
    }

    public function getPerPage(): int
    {
        return $this->params[ 'limit' ];
    }
}
