<?php

namespace VisionAura\LaravelCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\CoreException;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;
use VisionAura\LaravelCore\Models\CoreApplication;
use VisionAura\LaravelCore\Traits\HasErrorBag;

class ValidateApplication
{
    use HasErrorBag;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @throws CoreException
     * @throws InvalidStatusCodeException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-Application-Id')) {
            $this->getErrors()->push(
                'Missing information in request.',
                'Missing the \'X-Application-Id\' header.',
                'headers/X-Application-Id',
                Response::HTTP_BAD_REQUEST);
        }

        if (! $request->hasHeader('X-Application-Secret')) {
            $this->getErrors()->push(
                'Missing information in request.',
                'Missing the \'X-Application-Secret\' header.',
                'headers/X-Application-Secret',
                Response::HTTP_BAD_REQUEST);
        }

        $this->checkErrors();

        $application = CoreApplication::where('id', $request->header('X-Application-Id'))->first();

        if (! $application) {
            return $this->error(
                "Application error.",
                "The application '{$request->header('X-Application-Id')}' is not registered.",
                'headers/X-Application-Secret',
                412
            );
        }

        if ($request->header('X-Application-Secret') === $application->secret) {
            return $next($request);
        }

        return $this->error(
            'Application error.',
            'The application secret is incorrect.',
            'headers/X-Application-Secret',
            Response::HTTP_FORBIDDEN
        );
    }
}