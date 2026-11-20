<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaginationParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->query->all() as $name => $value) {
            if (! preg_match('/(?:^|_)page$/D', (string) $name)) {
                continue;
            }

            abort_unless(is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value) === 1
                && filter_var($value, FILTER_VALIDATE_INT) !== false, 404);
        }

        return $next($request);
    }
}
