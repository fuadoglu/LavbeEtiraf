<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PrivateResponseHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (ValidationException $exception) {
            $response = app(ExceptionHandler::class)->render($request, $exception);
        } catch (Throwable $exception) {
            $handler = app(ExceptionHandler::class);
            $handler->report($exception);
            $response = $handler->render($request, $exception);
        }
        $response->headers->set('Cache-Control', \App\Support\Http\ResponseHeaders::CACHE_PRIVATE_NO_STORE);
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Robots-Tag', \App\Support\Http\ResponseHeaders::ROBOTS_PRIVATE);
        $response->headers->set('Referrer-Policy', \App\Support\Http\ResponseHeaders::REFERRER_NO_REFERRER);

        return $response;
    }
}
