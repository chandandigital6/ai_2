<?php

namespace App\Http\Controllers\Extensions;

use App\Models\AiBrandAsset;
use App\Models\AiBrandProfile;
use App\Models\AiGeneration;
use App\Models\AiPlan;
use App\Models\AiPlanRequest;
use App\Models\AiPromptPreset;
use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Business;
use App\Models\Festival;
use App\Models\Order;
use App\Models\Report;
use App\Models\Setting;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialSchedule;
use App\Services\Extensions\AiPlanService;
use App\Services\Extensions\AiWalletService;
use App\Services\Extensions\CommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends ExtensionController
{
    public function businessWorkspace(Request $r, Business $business, string $section = 'ai')
    {
        $sections = ['ai' => ['AI studio', 'ai.view'], 'social' => ['Social publishing', 'social.view'], 'analytics' => ['Daily analytics', 'analytics.view'], 'commerce' => ['Shipping, GST & delivery rules', 'commerce.view'], 'accounting' => ['Accounting & cash flow', 'accounting.view'], 'delivery' => ['Delivery confirmation', 'delivery.view'], 'payments' => ['Gateway payments', 'payments.view']];
        abort_unless(isset($sections[$section]), 404);
        $this->business($r, $business, $sections[$section][1]);
        $data = [];
        if ($section === 'ai') {
            $data = ['wallet' => app(AiWalletService::class)->balance($business->id), 'generations' => AiGeneration::with('ai_media')->where('business_id', $business->id)->latest('id')->paginate(15),
                'entitlements' => app(AiPlanService::class)->entitlements($business->id),
                'plans' => AiPlan::where('is_active', true)->orderBy('price_paise')->get(),
                'requests' => AiPlanRequest::where('business_id', $business->id)->latest('id')->limit(15)->get(),
                'profile' => AiBrandProfile::where('business_id', $business->id)->first(),
                'assets' => AiBrandAsset::where('business_id', $business->id)->get(),
                'presets' => AiPromptPreset::where('business_id', $business->id)->orderBy('name')->get()];
        }
        if ($section === 'social') {
            $data = ['accounts' => SocialAccount::where('business_id', $business->id)->get(), 'posts' => SocialPost::with('social_post_platforms.social_account')->where('business_id', $business->id)->latest('id')->paginate(15), 'schedules' => SocialSchedule::where('business_id', $business->id)->latest('id')->get(), 'candidates' => [], 'festivals' => Festival::where('status', 'active')->where('festival_date', '>=', today())->orderBy('festival_date')->limit(30)->get()];
            $key = $r->session()->get('growya_social_connection');
            $cached = $key ? Cache::get('meta-result:'.hash('sha256', $key)) : null;
            if ($key && ! $cached) {
                $data['connectionError'] = 'Page selection has expired. Reconnect to retrieve your Facebook Pages and linked Instagram accounts.';
            }
            if ($cached) {
                $result = decrypt($cached);
                if ((int) $result['user_id'] === (int) $r->user()->id && (int) $result['business_id'] === (int) $business->id) {
                    $data['connectionAttempted'] = true;
                    $data['connectionKey'] = $key;
                    $data['candidates'] = collect($result['pages'])->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name'], 'instagram' => $p['instagram_business_account'] ?? null])->all();
                    $data['connectionError'] = $result['error'];
                }
            }
        }
        if ($section === 'analytics') {
            $data = ['rows' => DB::table('business_daily_analytics')->where('business_id', $business->id)->orderByDesc('date')->paginate(31)];
        }
        if ($section === 'commerce') {
            $data = ['policy' => app(CommerceService::class)->policy($business->id), 'states' => DB::table('states')->where('is_active', true)->orderBy('name')->pluck('name')];
        }
        if ($section === 'accounting') {
            $data = ['accounts' => DB::table('growya_journal_accounts')->where('business_id', $business->id)->orderBy('code')->get(), 'journals' => DB::table('growya_journals')->where('business_id', $business->id)->latest('id')->paginate(20)];
        }
        if ($section === 'delivery') {
            $data = ['orders' => Order::where('business_id', $business->id)->whereIn('status', ['shipped', 'delivered'])->latest('id')->paginate(15), 'policy' => app(CommerceService::class)->policy($business->id)];
        }
        if ($section === 'payments') {
            $data = ['payments' => DB::table('growya_gateway_payments as p')->join('orders as o', 'o.id', '=', 'p.order_id')->where('o.business_id', $business->id)->select('p.*', 'o.order_number')->orderByDesc('p.id')->paginate(20)];
        }

        return view('extensions.workspace', compact('business', 'section', 'sections', 'data'));
    }

    public function adminWorkspace(Request $r, string $kind = 'banners')
    {
        $catalog = ['banners' => ['Banners', Banner::class, 'banners.manage'], 'festivals' => ['Festival calendar', Festival::class, 'festivals.manage'], 'settings' => ['Presentation settings', Setting::class, 'settings.manage'], 'reports' => ['Content reports', Report::class, 'reports.manage'], 'audit' => ['Admin audit history', AuditLog::class, 'audit.view']];
        abort_unless(isset($catalog[$kind]), 404);
        $this->admin($r, $catalog[$kind][2]);
        $class = $catalog[$kind][1];
        $rows = $class::latest('id')->paginate(20);

        return view('extensions.admin', compact('catalog', 'kind', 'rows'));
    }
}
