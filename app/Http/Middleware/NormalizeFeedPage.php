<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeFeedPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query->has('page')) {
            $page = $request->query('page');
            abort_unless(is_string($page) && preg_match('/^[1-9][0-9]*$/D', $page) === 1
                && filter_var($page, FILTER_VALIDATE_INT) !== false, 404);

            if ($page === '1') {
                return redirect()->to($request->fullUrlWithoutQuery('page'), 301);
            }
        }

        return $next($request);
    }
}
