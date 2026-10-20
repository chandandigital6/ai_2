<?php

namespace App\Http\Controllers\Extensions;

use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Festival;
use App\Models\Report;
use App\Models\Setting;
use App\Services\Extensions\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminToolsController extends ExtensionController
{
    private const CATALOG = ['banners' => [Banner::class, 'banners.manage'], 'festivals' => [Festival::class, 'festivals.manage'], 'reports' => [Report::class, 'reports.manage'], 'settings' => [Setting::class, 'settings.manage'], 'audit' => [AuditLog::class, 'audit.view']];

    private const SETTINGS = ['branding' => ['site_title', 'support_email', 'support_phone'], 'marketplace' => ['welcome_text', 'show_banner'], 'notifications' => ['help_text']];

    private function model(Request $r, string $kind): string
    {
        abort_unless(isset(self::CATALOG[$kind]), 404);
        [$class,$permission] = self::CATALOG[$kind];
        $this->admin($r, $permission);

        return $class;
    }

    public function index(Request $r, string $kind)
    {
        $class = $this->model($r, $kind);

        return response()->json(['data' => $class::latest('id')->paginate(25)]);
    }

    public function save(Request $r, string $kind, ?int $item = null)
    {
        $class = $this->model($r, $kind);
        abort_if($kind === 'audit', 405);
        $row = $item ? $class::findOrFail($item) : new $class;
        $old = $row->toArray();
        $path = null;
        if ($kind === 'reports') {
            abort_unless($item, 405);
            $d = $r->validate(['status' => ['required', Rule::in(['open', 'reviewing', 'resolved', 'dismissed'])]]);
            $d += ['handled_by' => $r->user()->id, 'handled_at' => now()];
        } elseif ($kind === 'settings') {
            $d = $r->validate(['group_name' => ['required', Rule::in(array_keys(self::SETTINGS))], 'key_name' => ['required', 'string', 'max:60'], 'value' => ['nullable', 'string', 'max:3000'], 'is_public' => ['required', 'boolean']]);
            abort_unless(in_array($d['key_name'], self::SETTINGS[$d['group_name']], true), 422, 'Only non-secret presentation settings can be stored here.');
            $d['value_type'] = 'string';
            if (! $item) {
                $row = Setting::firstOrNew(['group_name' => $d['group_name'], 'key_name' => $d['key_name']]);
            }
        } elseif ($kind === 'festivals') {
            $d = $r->validate(['name' => ['required', 'string', 'max:200'], 'festival_date' => ['required', 'date_format:Y-m-d'], 'default_caption' => ['nullable', 'string', 'max:2200'], 'default_prompt' => ['nullable', 'string', 'max:8000'], 'region_filters' => ['nullable', 'array', 'max:50'], 'region_filters.*' => ['string', 'max:100'], 'status' => ['required', Rule::in(['active', 'inactive'])]]);
        } else {
            $d = $r->validate(['placement' => ['required', Rule::in(['home', 'marketplace', 'showroom'])], 'city_id' => ['nullable', 'integer', 'exists:cities,id'], 'image' => [$row->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'cta_text' => ['nullable', 'string', 'max:100'], 'cta_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'sort_order' => ['required', 'integer', 'min:0', 'max:10000'], 'status' => ['required', Rule::in(['active', 'inactive'])]]);
            unset($d['image']);
            if ($r->hasFile('image')) {
                $path = $r->file('image')->store('banners', 'public');
                abort_unless($path, 500);
                $d['media'] = $path;
            }
        }
        try {
            DB::transaction(function () use ($r, $kind, $row, $d, $old) {
                $row->fill($d)->save();
                AuditLog::create(['user_id' => $r->user()->id, 'action' => 'admin.'.$kind.'.save', 'auditable_type' => $kind, 'auditable_id' => $row->id, 'old_values' => $old, 'new_values' => $row->toArray()]);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }throw $e;
        }
        if ($kind === 'banners' && $path && ! empty($old['media'])) {
            Storage::disk('public')->delete($old['media']);
        }

        return $this->reply($r, $row->toArray(), $item ? 200 : 201);
    }

    public function destroy(Request $r, string $kind, int $item)
    {
        $class = $this->model($r, $kind);
        abort_unless(in_array($kind, ['banners', 'settings', 'festivals'], true), 405);
        $row = $class::findOrFail($item);
        DB::transaction(function () use ($r, $kind, $row) {
            AuditLog::create(['user_id' => $r->user()->id, 'action' => 'admin.'.$kind.'.delete', 'auditable_type' => $kind, 'auditable_id' => $row->id, 'old_values' => $row->toArray()]);
            $row->delete();
        });
        if ($kind === 'banners') {
            Storage::disk('public')->delete($row->media);
        }

        return $this->reply($r, ['deleted' => true]);
    }

    public function report(Request $r, ActivityService $service)
    {
        $d = $r->validate(['resource_type' => ['required', Rule::in(['business', 'product', 'service', 'offer'])], 'resource_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:10', 'max:3000']]);
        $service->target($d['resource_type'], $d['resource_id']);
        $row = Report::create(['user_id' => $r->user()->id, 'reportable_type' => $d['resource_type'], 'reportable_id' => $d['resource_id'], 'reason' => $d['reason'], 'status' => 'open']);

        return $this->reply($r, $row->toArray(), 201);
    }

    public function publicContent(Request $r)
    {
        $d = $r->validate(['placement' => ['nullable', Rule::in(['home', 'marketplace', 'showroom'])], 'city_id' => ['nullable', 'integer', 'exists:cities,id']]);
        $banners = Banner::where('status', 'active')->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->where(fn ($q) => $q->whereNull('city_id')->when(! empty($d['city_id']), fn ($q) => $q->orWhere('city_id', $d['city_id'])))->when(! empty($d['placement']), fn ($q) => $q->where('placement', $d['placement']))->orderBy('sort_order')->limit(30)->get()->map(fn ($b) => ['id' => $b->id, 'media_url' => Storage::disk('public')->url($b->media), 'cta_text' => $b->cta_text, 'cta_url' => $b->cta_url]);

        return response()->json(['data' => ['banners' => $banners, 'settings' => Setting::where('is_public', true)->get(['group_name', 'key_name', 'value'])->filter(fn ($s) => in_array($s->key_name, self::SETTINGS[$s->group_name] ?? [], true))->values(), 'festivals' => Festival::where('status', 'active')->where('festival_date', '>=', today())->orderBy('festival_date')->limit(30)->get(['id', 'name', 'festival_date', 'default_caption', 'region_filters'])]]);
    }
}
