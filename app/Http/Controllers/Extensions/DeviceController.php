<?php

namespace App\Http\Controllers\Extensions;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeviceController extends ExtensionController
{
    public function index(Request $request)
    {
        return response()->json(['data' => UserDevice::where('user_id', $request->user()->id)->get(['id', 'platform', 'app_version', 'is_active', 'last_seen_at'])]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['fcm_token' => ['required', 'string', 'min:20', 'max:4096'], 'platform' => ['required', Rule::in(['android', 'ios', 'web'])], 'app_version' => ['nullable', 'string', 'max:40']]);
        $hash = hash('sha256', $d['fcm_token']);
        $device = DB::transaction(function () use ($d, $hash, $request) {
            // Token registration is possession-based. Switching an installation's signed-in account revokes its previous recipient mapping.
            $old = UserDevice::where('fcm_token', $d['fcm_token'])->whereNull('token_hash')->lockForUpdate()->get();
            foreach ($old as $row) {
                $row->update(['is_active' => false]);
            }

            return UserDevice::updateOrCreate(['token_hash' => $hash], ['user_id' => $request->user()->id, 'session_token_id' => $request->user()->currentAccessToken()?->id, 'fcm_token' => $d['fcm_token'], 'platform' => $d['platform'], 'app_version' => $d['app_version'] ?? null, 'is_active' => true, 'last_seen_at' => now()]);
        }, 3);

        return response()->json(['data' => $device->only(['id', 'platform', 'app_version', 'is_active', 'last_seen_at'])], 201);
    }

    public function destroy(Request $request, int $device)
    {
        UserDevice::where('user_id', $request->user()->id)->findOrFail($device)->update(['is_active' => false]);

        return response()->json(['message' => 'Device unregistered.']);
    }

    public function acknowledge(Request $request, int $delivery)
    {
        $d = $request->validate(['event' => ['required', Rule::in(['delivered', 'opened'])]]);
        DB::transaction(function () use ($request, $delivery, $d) {
            $row = DB::table('growya_push_deliveries')->where('id', $delivery)->lockForUpdate()->first();
            abort_unless($row, 404);
            $recipient = DB::table('notification_recipients')->where('id', $row->recipient_id)->where('user_id', $request->user()->id)->first();
            abort_unless($recipient, 404);
            abort_unless(in_array($row->status, ['accepted', 'delivered', 'opened'], true), 422);
            $values = ['delivered_at' => $row->delivered_at ?? now(), 'updated_at' => now()];
            if ($d['event'] === 'opened') {
                $values['opened_at'] = $row->opened_at ?? now();
            }
            $values['status'] = $row->opened_at || $d['event'] === 'opened' ? 'opened' : 'delivered';
            DB::table('growya_push_deliveries')->where('id', $delivery)->update($values);
            $r = ['delivered_at' => $recipient->delivered_at ?? now(), 'updated_at' => now()];
            if ($d['event'] === 'opened') {
                $r['opened_at'] = $recipient->opened_at ?? now();
                $r['status'] = 'opened';
            }
            DB::table('notification_recipients')->where('id', $recipient->id)->update($r);
        }, 3);

        return response()->json(['message' => 'Acknowledged.']);
    }
}
