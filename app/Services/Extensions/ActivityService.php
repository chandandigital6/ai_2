<?php

namespace App\Services\Extensions;

use App\Models\Business;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityService
{
    public function target(string $type, int $id): array
    {
        $class = match ($type) {
            'business' => Business::class,'product' => Product::class,'service' => Service::class,'offer' => Offer::class,default => abort(422, 'Unsupported resource type.')
        };
        $model = $class::findOrFail($id);
        $business = $type === 'business' ? $model : Business::findOrFail($model->business_id);
        abort_unless($business->status === 'active' && $business->verification_status === 'approved', 404);
        if ($type !== 'business') {
            abort_unless($model->status === 'active', 404);
        }

        return [$model, $business];
    }

    public function record(Request $request, string $type, int $id, string $event = 'view'): void
    {
        [$model,$business] = $this->target($type, $id);
        DB::table('activity_events')->insert(['business_id' => $business->id, 'user_id' => $request->user()?->id, 'product_id' => $type === 'product' ? $id : null, 'service_id' => $type === 'service' ? $id : null, 'offer_id' => $type === 'offer' ? $id : null, 'event_type' => $event, 'source' => 'app', 'platform' => $request->is('api/*') ? 'api' : 'web', 'metadata' => json_encode(['resource_type' => $type, 'resource_id' => $id]), 'created_at' => now(), 'updated_at' => now()]);
        if ($event === 'view' && $request->user()) {
            // Lock the account because the original history table has no unique key.
            DB::transaction(function () use ($request, $type, $id) {
                DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
                DB::table('recently_viewed')->updateOrInsert(['user_id' => $request->user()->id, 'viewable_type' => $type, 'viewable_id' => $id], ['viewed_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
            }, 3);
        }
    }

    public function url(string $type, $model, Business $business): string
    {
        return match ($type) {
            'business' => route('public.business.home', $business->slug),'product' => route('public.business.product', [$business->slug, $model->slug]),'service' => route('public.business.service', [$business->slug, $model->slug]),default => route('public.business.offers', $business->slug)
        };
    }
}
