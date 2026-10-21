<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Models\ShareLink;
use App\Services\Extensions\ActivityService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ActivityController extends ExtensionController
{
    public function store(Request $r, ActivityService $service)
    {
        $d = $r->validate(['resource_type' => ['required', Rule::in(['business', 'product', 'service', 'offer'])], 'resource_id' => ['required', 'integer', 'min:1'], 'event_type' => ['required', Rule::in(['view', 'call_click', 'whatsapp_click', 'map_click', 'share'])]]);
        $service->record($r, $d['resource_type'], $d['resource_id'], $d['event_type']);

        return $this->reply($r, ['recorded' => true], 201);
    }

    public function history(Request $r, ActivityService $service)
    {
        $rows = DB::table('recently_viewed')->where('user_id', $r->user()->id)->orderByDesc('viewed_at')->paginate(20);
        $rows->setCollection($rows->getCollection()->map(function ($row) use ($service) {
            try {
                [$m,$b] = $service->target($row->viewable_type, $row->viewable_id);

                return ['id' => $row->id, 'resource_type' => $row->viewable_type, 'resource_id' => $row->viewable_id, 'name' => $m->name ?? $m->title, 'viewed_at' => $row->viewed_at, 'url' => $service->url($row->viewable_type, $m, $b)];
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                return null;
            }
        })->filter()->values());

        return response()->json(['data' => $rows]);
    }

    public function clear(Request $r)
    {
        DB::table('recently_viewed')->where('user_id', $r->user()->id)->delete();

        return $this->reply($r, ['cleared' => true]);
    }

    public function share(Request $r, ActivityService $service)
    {
        $d = $r->validate(['resource_type' => ['required', Rule::in(['business', 'product', 'service', 'offer'])], 'resource_id' => ['required', 'integer', 'min:1']]);
        $service->target($d['resource_type'], $d['resource_id']);
        $link = ShareLink::create(['shareable_type' => $d['resource_type'], 'shareable_id' => $d['resource_id'], 'code' => Str::random(24), 'source' => 'app', 'expires_at' => now()->addDays(30), 'clicks_count' => 0]);

        return response()->json(['data' => ['url' => url('/s/'.$link->code), 'code' => $link->code, 'expires_at' => $link->expires_at]], 201);
    }

    public function resolve(Request $r, string $code, ActivityService $service)
    {
        $link = ShareLink::where('code', $code)->firstOrFail();
        abort_if($link->expires_at?->isPast(), 410);
        [$m,$b] = $service->target($link->shareable_type, $link->shareable_id);
        $link->increment('clicks_count');
        $service->record($r, $link->shareable_type, $link->shareable_id, 'share_click');
        $url = $service->url($link->shareable_type, $m, $b);

        return $r->is('api/*') ? response()->json(['data' => ['url' => $url, 'resource_type' => $link->shareable_type, 'resource_id' => $link->shareable_id]]) : redirect()->to($url);
    }

    public function daily(Request $r, Business $business)
    {
        $this->business($r, $business, 'analytics.view');
        $d = $r->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $rows = DB::table('business_daily_analytics')->where('business_id', $business->id)->whereBetween('date', [$d['from'] ?? now()->subDays(30)->toDateString(), $d['to'] ?? now()->toDateString()])->orderByDesc('date')->paginate(31);

        return response()->json(['data' => $rows]);
    }

    public function search(Request $r)
    {
        $d = $r->validate(['query' => ['required', 'string', 'max:120'], 'city_id' => ['nullable', 'integer', 'exists:cities,id']]);
        $q = Business::where('status', 'active')->where('verification_status', 'approved')->where('name', 'like', '%'.$d['query'].'%');
        if (! empty($d['city_id'])) {
            $q->where('city_id', $d['city_id']);
        }
        $rows = $q->sponsoredFirst()->latest('id')->paginate(20);
        DB::table('search_logs')->insert(['user_id' => $r->user()?->id, 'query' => $d['query'], 'city_id' => $d['city_id'] ?? null, 'result_count' => $rows->total(), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['data' => $rows->through(fn ($b) => $b->only(['id', 'name', 'slug', 'logo', 'city_id', 'is_sponsored']))]);
    }
}
