<?php

namespace App\Http\Controllers\Extensions;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;

abstract class ExtensionController extends Controller
{
    protected function business(Request $request, Business $business, string $permission): void
    {
        abort_unless($request->user() && app(BusinessAccessService::class)->can($request->user(), $business, $permission), 403, 'Business permission required: '.$permission);
    }

    protected function admin(Request $request, string $permission = 'settings.manage'): void
    {
        abort_unless($request->user()?->status === 'active' && ($request->user()->hasRole('super_admin', 'web') || $request->user()->can($permission)), 403);
    }

    protected function reply(Request $request, array $data, int $status = 200)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['data' => $data], $status);
        }

        return back()->with('success', 'Saved successfully.');
    }
}
