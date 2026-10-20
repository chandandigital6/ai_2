<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\Product;
use App\Models\Service;
use App\Services\Extensions\ActivityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CapturePublicActivity
{
    public function handle(Request $r, Closure $next)
    {
        $response = $next($r);
        if (! $r->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }
        $name = (string) $r->route()?->getName();
        $type = null;
        $id = null;
        if (in_array($name, ['public.business.home', 'public.business.product', 'public.business.service'], true)) {
            $b = Business::where('slug', $r->route('slug'))->first();
            if (! $b) {
                return $response;
            }
            if ($name === 'public.business.home') {
                $type = 'business';
                $id = $b->id;
            } else {
                $type = $name === 'public.business.product' ? 'product' : 'service';
                $class = $type === 'product' ? Product::class : Service::class;
                $id = $class::where('business_id', $b->id)->where('slug', $r->route($type === 'product' ? 'productSlug' : 'serviceSlug'))->value('id');
            }
        }
        if ($id && $type) {
            $key = 'view:'.hash('sha256', ($r->user()?->id ?? $r->ip()).':'.$type.':'.$id);
            if (Cache::add($key, true, 60)) {
                try {
                    app(ActivityService::class)->record($r, $type, (int) $id);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return $response;
    }
}
