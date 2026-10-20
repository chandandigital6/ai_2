<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class OptionalGrowyaToken
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            Auth::shouldUse('sanctum');
            $user = Auth::guard('sanctum')->user();
            abort_unless($user, 401);
            abort_unless($user->status === 'active', 403);
            $token = $user->currentAccessToken();
            abort_unless($token instanceof PersonalAccessToken && $token->can('growya'), 403);
            $request->setUserResolver(fn () => $user);
        }

        return $next($request);
    }
}
