<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ThrottleByRoute extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (! is_numeric($maxAttempts) && func_num_args() === 3) {
            return parent::handle($request, $next, $maxAttempts);
        }

        // Named limiters (login, 2FA, AI) retain their deliberate shared scope.
        // Numeric limits belong to a route template, never a concrete record ID.
        if (is_numeric($maxAttempts)) {
            $route = $request->route();
            $scope = $route?->getName() ?: $request->method().':'.$route?->uri();
            $prefix .= 'growya-route:'.hash('sha256', $scope.':'.$maxAttempts.':'.$decayMinutes).':';
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
