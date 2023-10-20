<?php

namespace VisionAura\LaravelCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VisionAura\LaravelCore\Models\Application;
use VisionAura\LaravelCore\Traits\HttpResponses;

class ValidateApplication
{
    use HttpResponses;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-Application-Id')) {
            return $this->error(message: 'Missing the \'X-Application-Id\' header', code: 400);
        }

        if (! $request->hasHeader('X-Application-Secret')) {
            return $this->error(message: 'Missing the \'X-Application-Secret\' header', code: 400);
        }

        $application = Application::where('id', $request->header('X-Application-Id'))->first();

        if (! $application) {
            return $this->error(
                message: "The application '{$request->header('X-Application-Id')}' is not registered.",
                code: 412
            );
        }

        if ($request->header('X-Application-Secret') === $application->secret) {
            return $next($request);
        }

        return $this->error(message: 'The application secret is incorrect.', code: 403);
    }
}