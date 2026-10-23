<?php

namespace App\Services\Extensions;

class WebPushConfig
{
    public function firebase(): array
    {
        // Only public Firebase Web App settings; never expose the service account.
        return array_filter([
            'apiKey' => config('growya_extensions.fcm.web.api_key'),
            'authDomain' => config('growya_extensions.fcm.web.auth_domain'),
            'projectId' => config('growya_extensions.fcm.web.project_id'),
            'messagingSenderId' => config('growya_extensions.fcm.web.sender_id'),
            'appId' => config('growya_extensions.fcm.web.app_id'),
        ], fn ($v) => is_string($v) && $v !== '');
    }

    public function ready(): bool
    {
        return config('growya_extensions.fcm.enabled')
            && config('growya_extensions.fcm.web.enabled')
            && count(array_intersect(['apiKey', 'projectId', 'messagingSenderId', 'appId'], array_keys($this->firebase()))) === 4
            && filled(config('growya_extensions.fcm.web.vapid_key'));
    }
}
