<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Extensions\DeviceController;
use App\Models\UserDevice;
use App\Services\Extensions\WebPushConfig;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrowyaWebPushController extends Controller
{
    public function config(Request $request, WebPushConfig $config)
    {
        return response()->json(['enabled' => (bool) $config->ready(), 'firebase' => $config->firebase(),
            'vapid_key' => config('growya_extensions.fcm.web.vapid_key'),
            'devices' => UserDevice::where('user_id', $request->user()->id)->where('platform', 'web')->where('is_active', true)->count(),
            'recent_deliveries' => DB::table('growya_push_deliveries as d')->join('notification_recipients as r', 'r.id', '=', 'd.recipient_id')
                ->where('r.user_id', $request->user()->id)->orderByDesc('d.id')->limit(5)->get(['d.id', 'd.status', 'd.error_code', 'd.updated_at']),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, WebPushConfig $config)
    {
        abort_unless($config->ready(), 503, 'Browser push is not configured. Add Firebase Web App settings and the public VAPID key.');
        $request->merge(['platform' => 'web', 'app_version' => 'growya-web-20261023']);
        $response = app(DeviceController::class)->store($request);
        $request->session()->put('growya_web_device_id', $response->getData(true)['data']['id']);

        return $response;
    }

    public function disable(Request $request)
    {
        UserDevice::where('user_id', $request->user()->id)->where('platform', 'web')
            ->whereKey($request->session()->get('growya_web_device_id', 0))->update(['is_active' => false]);
        $request->session()->forget('growya_web_device_id');

        return response()->json(['message' => 'Alerts disabled for this browser session.']);
    }

    public function test(Request $request, WebPushConfig $config, GrowyaNotificationService $notifications)
    {
        abort_unless($config->ready(), 503, 'Firebase browser push is not configured.');
        abort_unless(UserDevice::where('user_id', $request->user()->id)->where('platform', 'web')->where('is_active', true)
            ->whereKey($request->session()->get('growya_web_device_id', 0))->exists(), 422, 'Enable alerts in this browser first.');
        $notifications->send([$request->user()->id], 'push_test', 'Growya alerts are ready', 'This is your test notification. Tap to open your notifications.', route('growya.notifications.index', absolute: false));

        return response()->json(['message' => 'Test notification queued. The notifications worker must be running.']);
    }

    public function worker(WebPushConfig $config)
    {
        return response()->view('notifications.worker', ['firebase' => $config->ready() ? $config->firebase() : null])
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Service-Worker-Allowed', '/');
    }
}
