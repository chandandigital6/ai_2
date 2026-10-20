<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessAccess
{
    public function __construct(
        protected BusinessAccessService $access
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $permission = 'business.view'
    ): Response {

        $user = $request->user();

        abort_unless($user, 401);

        $business = $request->route('business');

        // Require resolved route model binding.
        abort_unless(
            $business instanceof Business,
            404
        );

        abort_unless(
            $this->access->can(
                $user,
                $business,
                $permission
            ),
            403,
            'You do not have permission for this business.'
        );

        return $next($request);
    }
}
