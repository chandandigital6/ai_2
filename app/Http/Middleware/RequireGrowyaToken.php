<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireGrowyaToken
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->tokenCan('growya'), 403, 'This token cannot access Growya.');

        return $next($request);
    }
}
