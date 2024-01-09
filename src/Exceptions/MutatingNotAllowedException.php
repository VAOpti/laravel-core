<?php

namespace VisionAura\LaravelCore\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class MutatingNotAllowedException extends CoreException
{
    public function __construct()
    {
        $this->errorBag = ErrorBag::make(
            title: __('core::errors.Can not modify this resource'),
            description: 'The resource is in a muted state.',
            status: Response::HTTP_BAD_REQUEST
        )->bag;

        parent::__construct($this->errorBag, __('Can not modify this resource.'), Response::HTTP_BAD_REQUEST);
    }
}
