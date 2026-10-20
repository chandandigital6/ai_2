<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Closure;
use Illuminate\Http\Request;

class AuthorizeBusinessModule
{
    public function handle(Request $request, Closure $next)
    {
        $business = $request->route('business');
        [$class, $action] = $request->attributes->get('domain_action') ?? [$request->route()->getControllerClass() ?? '', $request->route()->getActionMethod()];
        $controller = class_basename($class);
        $pair = config("business_permissions.controllers.$controller");
        // These actions are customer/public workflows; their controllers check the customer/context.
        $customer = [
            'GrowyaChatController' => ['start'],
            'GrowyaNearbyController' => ['index'],
            'BusinessReviewController' => ['store', 'update', 'publicIndex'],
            'ProductEnquiryController' => ['create', 'store'],
        ];
        if ($business instanceof Business && $pair && ! in_array($action, $customer[$controller] ?? [], true)) {
            $write = ! $request->isMethodSafe();
            $permission = $pair[$write ? 1 : 0];
            if (in_array($controller, ['ProductController', 'ServiceController'], true)) {
                $prefix = $controller === 'ProductController' ? 'products' : 'services';
                $permission = $prefix.'.'.match ($action) {
                    'create', 'store' => 'create', 'edit', 'update' => 'update', 'destroy' => 'delete', default => 'view'
                };
            }
            if ($controller === 'TenantCatalogController') {
                $permission = ($request->route('kind') === 'attributes' ? 'attributes' : 'categories').'.'.($write ? 'manage' : 'view');
            }
            if ($controller === 'TenantCatalogController' && in_array($action, ['storeValue', 'destroyValue'], true)) {
                $permission = 'attributes.manage';
            }
            if ($controller === 'BusinessStaffController' && in_array($action, ['store', 'update', 'destroy'], true)) {
                $permission = 'staff.manage';
            }
            if ($controller === 'GrowyaCrmStaffController' && in_array($action, ['assign', 'target'], true)) {
                $permission = 'customers.assign';
            }
            abort_unless($request->user() && app(BusinessAccessService::class)->can($request->user(), $business, $permission), 403, 'This business permission has not been granted: '.$permission);
        }

        return $next($request);
    }
}
