<?php

namespace App\Services\Extensions;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function available(): bool
    {
        return config('growya_extensions.otp.driver') === 'http' && str_starts_with((string) config('growya_extensions.otp.url'), 'https://') && filled(config('growya_extensions.otp.token'));
    }

    public function send(string $phone, string $code, string $purpose): void
    {
        abort_unless($this->available(), 503, 'SMS service has not been configured.');
        // Fixed server-owned URL; adapt these fields to your DLT-approved SMS provider.
        $response = Http::withToken(config('growya_extensions.otp.token'))->acceptJson()->timeout(15)->withOptions(['allow_redirects' => false])->post(config('growya_extensions.otp.url'), [
            'phone' => '91'.$phone, 'otp' => $code, 'purpose' => $purpose,
            'sender' => config('growya_extensions.otp.sender'), 'template_id' => config('growya_extensions.otp.template_id'),
        ]);
        abort_unless($response->successful(), 503, 'SMS provider could not accept the message.');
    }
}
