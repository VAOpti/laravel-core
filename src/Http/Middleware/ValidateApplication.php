<?php

namespace VisionAura\LaravelCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Exceptions\InvalidStatusCodeException;
use VisionAura\LaravelCore\Models\Application;
use VisionAura\LaravelCore\Traits\HttpResponses;

class ValidateApplication
{
    use HttpResponses;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @throws InvalidStatusCodeException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-Application-Id')) {
            $this->pushError(
                'Missing information in request.',
                'Missing the \'X-Application-Id\' header.',
                code: Response::HTTP_BAD_REQUEST);
        }

        if (! $request->hasHeader('X-Application-Secret')) {
            $this->pushError(
                'Missing information in request.',
                'Missing the \'X-Application-Secret\' header.',
                code: Response::HTTP_BAD_REQUEST);
        }

        if ($this->hasErrors()) {
            return $this->buildErrors();
        }

        $application = Application::where('id', $request->header('X-Application-Id'))->first();

        if (! $application) {
            return $this->error(
                "Application error.",
                "The application '{$request->header('X-Application-Id')}' is not registered.",
                code: 412
            );
        }

        if ($request->header('X-Application-Secret') === $application->secret) {
            return $next($request);
        }

        return $this->error('Application error.', 'The application secret is incorrect.', code: Response::HTTP_FORBIDDEN);
    }
}