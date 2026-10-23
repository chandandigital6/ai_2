<?php

namespace App\Listeners;

use App\Models\UserDevice;
use Illuminate\Auth\Events\Logout;

class RevokeBrowserPushOnLogout
{
    public function handle(Logout $event): void
    {
        if ($event->guard !== 'web' || ! $event->user || ! request()->hasSession()) {
            return;
        }
        $id = request()->session()->pull('growya_web_device_id');
        if ($id) {
            UserDevice::whereKey($id)->where('user_id', $event->user->id)->where('platform', 'web')->update(['is_active' => false]);
        }
    }
}
